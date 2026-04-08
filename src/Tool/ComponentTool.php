<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiToolkitPencilDev\Tool;

use CarmeloSantana\PHPAgents\Contract\ToolInterface;
use CarmeloSantana\PHPAgents\Tool\Tool;
use CarmeloSantana\PHPAgents\Tool\ToolResult;
use CarmeloSantana\PHPAgents\Tool\Parameter\EnumParameter;
use CarmeloSantana\PHPAgents\Tool\Parameter\StringParameter;
use CarmeloSantana\CoquiToolkitPencilDev\PenDocument;

/**
 * Reusable component creation and instantiation in .pen documents.
 */
final readonly class ComponentTool
{
    public function __construct(
        private string $workspacePath,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'pencil_component',
            description: 'Manage reusable components in a Pencil .pen document — create components, instantiate them as ref elements, list all components, get component details, or update ref instance overrides.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Component operation to perform.',
                    values: ['create', 'instantiate', 'list', 'get', 'update_instance'],
                    required: true,
                ),
                new StringParameter(
                    'path',
                    'Path to the .pen file (relative to workspace).',
                    required: true,
                ),
                new StringParameter(
                    'component_id',
                    'ID of the component (required for create from existing element, instantiate, get).',
                    required: false,
                ),
                new StringParameter(
                    'instance_id',
                    'ID of a ref instance (required for update_instance).',
                    required: false,
                ),
                new StringParameter(
                    'parent_id',
                    'Parent element ID to place the new instance into.',
                    required: false,
                ),
                new StringParameter(
                    'overrides',
                    'JSON object of property overrides for instantiate/update_instance. Applied to the ref\'s top-level properties.',
                    required: false,
                ),
                new StringParameter(
                    'descendants',
                    'JSON object of descendant overrides for instantiate/update_instance. Keys are descendant IDs, values are property objects. Example: {"child_id": {"content": "New text"}}',
                    required: false,
                ),
                new StringParameter(
                    'name',
                    'Component name (optional for create).',
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
            'create' => $this->create($args),
            'instantiate' => $this->instantiate($args),
            'list' => $this->listComponents($args),
            'get' => $this->get($args),
            'update_instance' => $this->updateInstance($args),
            default => ToolResult::error("Unknown action: {$action}"),
        };
    }

    /**
     * @param array<string, mixed> $args
     */
    private function create(array $args): ToolResult
    {
        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [$path, $doc] = $pathResult;

        $componentId = trim((string) ($args['component_id'] ?? ''));
        $name = trim((string) ($args['name'] ?? ''));

        if ($componentId !== '') {
            // Mark existing element as reusable.
            $element = $doc->findById($componentId);
            if ($element === null) {
                return ToolResult::error(sprintf('Element "%s" not found.', $componentId));
            }

            $updates = ['reusable' => true];
            if ($name !== '') {
                $updates['name'] = $name;
            }

            $doc = $doc->withUpdatedElement($componentId, $updates);
        } else {
            return ToolResult::error('The "component_id" parameter is required. Provide the ID of an existing element to mark as reusable.');
        }

        try {
            $doc->save($path);
        } catch (\RuntimeException $e) {
            return ToolResult::error($e->getMessage());
        }

        return ToolResult::success(sprintf(
            'Marked element [%s]%s as a reusable component.',
            $componentId,
            $name !== '' ? sprintf(' "%s"', $name) : '',
        ));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function instantiate(array $args): ToolResult
    {
        $componentId = trim((string) ($args['component_id'] ?? ''));
        if ($componentId === '') {
            return ToolResult::error('The "component_id" parameter is required for instantiate.');
        }

        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [$path, $doc] = $pathResult;

        // Verify the component exists and is reusable.
        $component = $doc->findById($componentId);
        if ($component === null) {
            return ToolResult::error(sprintf('Component "%s" not found.', $componentId));
        }
        if (empty($component['reusable'])) {
            return ToolResult::error(sprintf('Element "%s" is not a reusable component. Use create action first.', $componentId));
        }

        $refId = PenDocument::generateId();
        $ref = [
            'id' => $refId,
            'type' => 'ref',
            'ref' => $componentId,
            'x' => 0,
            'y' => 0,
        ];

        // Apply overrides.
        $overrides = $this->parseJson($args, 'overrides');
        if ($overrides instanceof ToolResult) {
            return $overrides;
        }
        if ($overrides !== null) {
            $ref = array_merge($ref, $overrides);
            $ref['id'] = $refId;
            $ref['type'] = 'ref';
            $ref['ref'] = $componentId;
        }

        // Apply descendants overrides.
        $descendants = $this->parseJson($args, 'descendants');
        if ($descendants instanceof ToolResult) {
            return $descendants;
        }
        if ($descendants !== null) {
            $ref['descendants'] = $descendants;
        }

        $parentId = trim((string) ($args['parent_id'] ?? ''));
        $doc = $doc->withElement($ref, $parentId !== '' ? $parentId : null);

        try {
            $doc->save($path);
        } catch (\RuntimeException $e) {
            return ToolResult::error($e->getMessage());
        }

        return ToolResult::success(sprintf(
            'Created instance [%s] of component [%s]%s.',
            $refId,
            $componentId,
            $parentId !== '' ? sprintf(' in parent [%s]', $parentId) : '',
        ));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function listComponents(array $args): ToolResult
    {
        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [, $doc] = $pathResult;

        $components = $doc->getComponents();
        $instances = $doc->getInstances();

        if ($components === []) {
            return ToolResult::success('No reusable components found in this document.');
        }

        // Map component ID → instance count.
        $instanceCounts = [];
        foreach ($instances as $inst) {
            $refTarget = $inst['ref'] ?? '';
            $instanceCounts[$refTarget] = ($instanceCounts[$refTarget] ?? 0) + 1;
        }

        $lines = [];
        foreach ($components as $comp) {
            $id = $comp['id'] ?? '?';
            $name = $comp['name'] ?? '';
            $type = $comp['type'] ?? 'unknown';
            $count = $instanceCounts[$id] ?? 0;

            $line = sprintf('  [%s] %s', $id, $type);
            if ($name !== '') {
                $line .= sprintf(' "%s"', $name);
            }
            $line .= sprintf(' — %d instance(s)', $count);
            $lines[] = $line;
        }

        return ToolResult::success(sprintf(
            "%d component(s):\n%s",
            count($components),
            implode("\n", $lines),
        ));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function get(array $args): ToolResult
    {
        $componentId = trim((string) ($args['component_id'] ?? ''));
        if ($componentId === '') {
            return ToolResult::error('The "component_id" parameter is required for get.');
        }

        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [, $doc] = $pathResult;

        $component = $doc->findById($componentId);
        if ($component === null) {
            return ToolResult::error(sprintf('Component "%s" not found.', $componentId));
        }

        // Gather instances of this component.
        $instances = $doc->getInstances();
        $myInstances = array_filter($instances, fn(array $inst): bool => ($inst['ref'] ?? '') === $componentId);

        $output = sprintf("Component [%s]:\n%s\n\n%d instance(s):",
            $componentId,
            json_encode($component, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            count($myInstances),
        );

        foreach ($myInstances as $inst) {
            $instId = $inst['id'] ?? '?';
            $hasDescendants = !empty($inst['descendants']);
            $output .= sprintf("\n  [%s]%s", $instId, $hasDescendants ? ' (has descendant overrides)' : '');
        }

        return ToolResult::success($output);
    }

    /**
     * @param array<string, mixed> $args
     */
    private function updateInstance(array $args): ToolResult
    {
        $instanceId = trim((string) ($args['instance_id'] ?? ''));
        if ($instanceId === '') {
            return ToolResult::error('The "instance_id" parameter is required for update_instance.');
        }

        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [$path, $doc] = $pathResult;

        $instance = $doc->findById($instanceId);
        if ($instance === null) {
            return ToolResult::error(sprintf('Instance "%s" not found.', $instanceId));
        }
        if (($instance['type'] ?? '') !== 'ref') {
            return ToolResult::error(sprintf('Element "%s" is not a ref instance (type: %s).', $instanceId, $instance['type'] ?? 'unknown'));
        }

        $updates = [];

        // Apply top-level overrides.
        $overrides = $this->parseJson($args, 'overrides');
        if ($overrides instanceof ToolResult) {
            return $overrides;
        }
        if ($overrides !== null) {
            $updates = array_merge($updates, $overrides);
        }

        // Apply descendants overrides.
        $descendants = $this->parseJson($args, 'descendants');
        if ($descendants instanceof ToolResult) {
            return $descendants;
        }
        if ($descendants !== null) {
            $existing = $instance['descendants'] ?? [];
            $updates['descendants'] = array_merge($existing, $descendants);
        }

        if ($updates === []) {
            return ToolResult::error('Provide "overrides" and/or "descendants" to update.');
        }

        // Protect ref type fields.
        unset($updates['id'], $updates['type'], $updates['ref']);

        $doc = $doc->withUpdatedElement($instanceId, $updates);

        try {
            $doc->save($path);
        } catch (\RuntimeException $e) {
            return ToolResult::error($e->getMessage());
        }

        return ToolResult::success(sprintf(
            'Updated instance [%s]: %s',
            $instanceId,
            implode(', ', array_keys($updates)),
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
    private function parseJson(array $args, string $key): array|ToolResult|null
    {
        $json = trim((string) ($args[$key] ?? ''));
        if ($json === '') {
            return null;
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return ToolResult::error(sprintf('Invalid %s JSON: %s', $key, $e->getMessage()));
        }

        if (!is_array($data)) {
            return ToolResult::error(sprintf('"%s" must be a JSON object.', $key));
        }

        return $data;
    }
}
