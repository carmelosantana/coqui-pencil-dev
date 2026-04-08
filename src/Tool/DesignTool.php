<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiToolkitPencilDev\Tool;

use CarmeloSantana\PHPAgents\Contract\ToolInterface;
use CarmeloSantana\PHPAgents\Tool\Tool;
use CarmeloSantana\PHPAgents\Tool\ToolResult;
use CarmeloSantana\PHPAgents\Tool\Parameter\EnumParameter;
use CarmeloSantana\PHPAgents\Tool\Parameter\StringParameter;
use CarmeloSantana\CoquiToolkitPencilDev\PenDocument;
use CarmeloSantana\CoquiToolkitPencilDev\Schema\PenSchema;

/**
 * Element manipulation within .pen documents: insert, get, update, move, copy, delete, list.
 */
final readonly class DesignTool
{
    public function __construct(
        private string $workspacePath,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'pencil_design',
            description: 'Manipulate design elements in a Pencil .pen document — insert new elements, get/update/move/copy/delete existing ones, or list elements.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Design operation to perform.',
                    values: ['insert', 'get', 'update', 'move', 'copy', 'delete', 'list'],
                    required: true,
                ),
                new StringParameter(
                    'path',
                    'Path to the .pen file (relative to workspace).',
                    required: true,
                ),
                new StringParameter(
                    'element_id',
                    'Element ID (required for get, update, move, copy, delete).',
                    required: false,
                ),
                new EnumParameter(
                    'type',
                    'Element type to insert (required for insert).',
                    values: PenSchema::ELEMENT_TYPES,
                    required: false,
                ),
                new StringParameter(
                    'parent_id',
                    'Parent element ID to insert into or move to. If omitted, operates on root children.',
                    required: false,
                ),
                new StringParameter(
                    'properties',
                    'JSON object of element properties (for insert/update). Example: {"width": 200, "height": 100, "fills": [{"type": "color", "color": "#3B82F6"}]}',
                    required: false,
                ),
                new StringParameter(
                    'name',
                    'Element name/label (optional for insert).',
                    required: false,
                ),
                new EnumParameter(
                    'filter_type',
                    'Filter elements by type (optional for list).',
                    values: PenSchema::ELEMENT_TYPES,
                    required: false,
                ),
            ],
            callback: fn(array $args) => $this->execute($args),
        );
    }

    /**
     * @param array<string, mixed> $args
     */
    private function execute(array $args): ToolResult
    {
        $action = trim((string) ($args['action'] ?? ''));

        return match ($action) {
            'insert' => $this->insert($args),
            'get' => $this->get($args),
            'update' => $this->update($args),
            'move' => $this->move($args),
            'copy' => $this->copy($args),
            'delete' => $this->delete($args),
            'list' => $this->list($args),
            default => ToolResult::error("Unknown action: {$action}"),
        };
    }

    /**
     * @param array<string, mixed> $args
     */
    private function insert(array $args): ToolResult
    {
        $type = trim((string) ($args['type'] ?? ''));
        if ($type === '') {
            return ToolResult::error('The "type" parameter is required for insert.');
        }

        if (!in_array($type, PenSchema::ELEMENT_TYPES, true)) {
            return ToolResult::error(sprintf('Unknown element type "%s". Valid: %s', $type, implode(', ', PenSchema::ELEMENT_TYPES)));
        }

        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [$path, $doc] = $pathResult;

        $id = PenDocument::generateId();
        $element = PenSchema::defaultElement($type, $id);

        // Apply custom name.
        $name = trim((string) ($args['name'] ?? ''));
        if ($name !== '') {
            $element['name'] = $name;
        }

        // Apply custom properties.
        $properties = $this->parseProperties($args);
        if ($properties instanceof ToolResult) {
            return $properties;
        }
        if ($properties !== null) {
            $element = array_merge($element, $properties);
            $element['id'] = $id;
            $element['type'] = $type;
        }

        // Validate the element.
        $errors = PenSchema::validateElement($element);
        if ($errors !== []) {
            return ToolResult::error(sprintf("Invalid element:\n%s", implode("\n", $errors)));
        }

        $parentId = $this->getParentId($args);
        if ($parentId !== null) {
            $parent = $doc->findById($parentId);
            if ($parent === null) {
                return ToolResult::error(sprintf('Parent element "%s" not found.', $parentId));
            }
            $parentType = $parent['type'] ?? '';
            if (!in_array($parentType, PenSchema::CONTAINER_TYPES, true)) {
                return ToolResult::error(sprintf('Element type "%s" cannot contain children. Only frame and group can.', $parentType));
            }
        }

        $doc = $doc->withElement($element, $parentId);

        try {
            $doc->save($path);
        } catch (\RuntimeException $e) {
            return ToolResult::error($e->getMessage());
        }

        return ToolResult::success(sprintf(
            'Inserted %s element [%s]%s%s',
            $type,
            $id,
            $name !== '' ? sprintf(' "%s"', $name) : '',
            $parentId !== null ? sprintf(' into parent [%s]', $parentId) : ' at root',
        ));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function get(array $args): ToolResult
    {
        $elementId = $this->requireElementId($args);
        if ($elementId instanceof ToolResult) {
            return $elementId;
        }

        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [, $doc] = $pathResult;

        $element = $doc->findById($elementId);
        if ($element === null) {
            return ToolResult::error(sprintf('Element "%s" not found.', $elementId));
        }

        return ToolResult::success(json_encode($element, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function update(array $args): ToolResult
    {
        $elementId = $this->requireElementId($args);
        if ($elementId instanceof ToolResult) {
            return $elementId;
        }

        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [$path, $doc] = $pathResult;

        $existing = $doc->findById($elementId);
        if ($existing === null) {
            return ToolResult::error(sprintf('Element "%s" not found.', $elementId));
        }

        $properties = $this->parseProperties($args);
        if ($properties instanceof ToolResult) {
            return $properties;
        }
        if ($properties === null) {
            return ToolResult::error('The "properties" parameter is required for update. Provide a JSON object of properties to change.');
        }

        // Don't allow changing type or id via update.
        unset($properties['id'], $properties['type']);

        $doc = $doc->withUpdatedElement($elementId, $properties);

        try {
            $doc->save($path);
        } catch (\RuntimeException $e) {
            return ToolResult::error($e->getMessage());
        }

        return ToolResult::success(sprintf(
            'Updated element [%s]: %s',
            $elementId,
            implode(', ', array_keys($properties)),
        ));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function move(array $args): ToolResult
    {
        $elementId = $this->requireElementId($args);
        if ($elementId instanceof ToolResult) {
            return $elementId;
        }

        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [$path, $doc] = $pathResult;

        $element = $doc->findById($elementId);
        if ($element === null) {
            return ToolResult::error(sprintf('Element "%s" not found.', $elementId));
        }

        // Apply position update from properties if provided.
        $properties = $this->parseProperties($args);
        if ($properties instanceof ToolResult) {
            return $properties;
        }
        if ($properties !== null) {
            if (isset($properties['x'])) {
                $element['x'] = $properties['x'];
            }
            if (isset($properties['y'])) {
                $element['y'] = $properties['y'];
            }
        }

        // Remove from current location.
        $doc = $doc->withoutElement($elementId);

        // Re-insert at target parent (or root).
        $parentId = $this->getParentId($args);
        if ($parentId !== null) {
            $parent = $doc->findById($parentId);
            if ($parent === null) {
                return ToolResult::error(sprintf('Target parent "%s" not found.', $parentId));
            }
        }

        $doc = $doc->withElement($element, $parentId);

        try {
            $doc->save($path);
        } catch (\RuntimeException $e) {
            return ToolResult::error($e->getMessage());
        }

        return ToolResult::success(sprintf(
            'Moved element [%s]%s',
            $elementId,
            $parentId !== null ? sprintf(' into [%s]', $parentId) : ' to root',
        ));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function copy(array $args): ToolResult
    {
        $elementId = $this->requireElementId($args);
        if ($elementId instanceof ToolResult) {
            return $elementId;
        }

        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [$path, $doc] = $pathResult;

        $parentId = $this->getParentId($args);
        $result = $doc->copyElement($elementId, $parentId);
        if ($result === null) {
            return ToolResult::error(sprintf('Element "%s" not found.', $elementId));
        }

        try {
            $result['document']->save($path);
        } catch (\RuntimeException $e) {
            return ToolResult::error($e->getMessage());
        }

        return ToolResult::success(sprintf(
            'Copied element [%s] → new element [%s]%s',
            $elementId,
            $result['element']['id'],
            $parentId !== null ? sprintf(' in parent [%s]', $parentId) : '',
        ));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function delete(array $args): ToolResult
    {
        $elementId = $this->requireElementId($args);
        if ($elementId instanceof ToolResult) {
            return $elementId;
        }

        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [$path, $doc] = $pathResult;

        $existing = $doc->findById($elementId);
        if ($existing === null) {
            return ToolResult::error(sprintf('Element "%s" not found.', $elementId));
        }

        $doc = $doc->withoutElement($elementId);

        try {
            $doc->save($path);
        } catch (\RuntimeException $e) {
            return ToolResult::error($e->getMessage());
        }

        return ToolResult::success(sprintf('Deleted element [%s].', $elementId));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function list(array $args): ToolResult
    {
        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [, $doc] = $pathResult;

        $parentId = $this->getParentId($args);
        $filterType = trim((string) ($args['filter_type'] ?? ''));

        $elements = $doc->listElements(
            $parentId !== '' ? $parentId : null,
            $filterType !== '' ? $filterType : null,
        );

        if ($elements === []) {
            return ToolResult::success('No elements found.');
        }

        $lines = [];
        foreach ($elements as $el) {
            $type = $el['type'] ?? 'unknown';
            $id = $el['id'] ?? '?';
            $name = $el['name'] ?? '';
            $childCount = isset($el['children']) ? count($el['children']) : 0;

            $line = sprintf('  %s [%s]', $type, $id);
            if ($name !== '') {
                $line .= sprintf(' "%s"', $name);
            }
            if ($childCount > 0) {
                $line .= sprintf(' (%d children)', $childCount);
            }
            $lines[] = $line;
        }

        $scope = $parentId !== null && $parentId !== '' ? sprintf(' in [%s]', $parentId) : ' at root';

        return ToolResult::success(sprintf(
            "%d element(s)%s:\n%s",
            count($elements),
            $scope,
            implode("\n", $lines),
        ));
    }

    // ─── Private Helpers ───────────────────────────────────────────────

    /**
     * @param array<string, mixed> $args
     * @return array{string, PenDocument}|ToolResult
     */
    private function loadDocument(array $args): array|ToolResult
    {
        $path = trim((string) ($args['path'] ?? ''));
        if ($path === '') {
            return ToolResult::error('The "path" parameter is required.');
        }

        if (!str_starts_with($path, '/')) {
            $path = rtrim($this->workspacePath, '/') . '/' . $path;
        }

        try {
            $doc = PenDocument::fromFile($path);
        } catch (\RuntimeException|\JsonException $e) {
            return ToolResult::error($e->getMessage());
        }

        return [$path, $doc];
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>|ToolResult|null
     */
    private function parseProperties(array $args): array|ToolResult|null
    {
        $json = trim((string) ($args['properties'] ?? ''));
        if ($json === '') {
            return null;
        }

        try {
            $props = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return ToolResult::error(sprintf('Invalid properties JSON: %s', $e->getMessage()));
        }

        if (!is_array($props)) {
            return ToolResult::error('Properties must be a JSON object.');
        }

        return $props;
    }

    /**
     * @param array<string, mixed> $args
     */
    private function requireElementId(array $args): string|ToolResult
    {
        $id = trim((string) ($args['element_id'] ?? ''));
        if ($id === '') {
            return ToolResult::error('The "element_id" parameter is required.');
        }
        return $id;
    }

    /**
     * @param array<string, mixed> $args
     */
    private function getParentId(array $args): ?string
    {
        $parentId = trim((string) ($args['parent_id'] ?? ''));
        return $parentId !== '' ? $parentId : null;
    }
}
