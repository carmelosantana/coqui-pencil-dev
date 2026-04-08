<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiToolkitPencilDev;

use CarmeloSantana\CoquiToolkitPencilDev\Schema\PenSchema;

/**
 * Immutable value object for .pen file manipulation.
 *
 * All mutation methods return new instances — the original is never modified.
 */
final readonly class PenDocument
{
    /**
     * @param array<string, mixed> $data Raw .pen document data.
     */
    private function __construct(
        private array $data,
    ) {}

    /**
     * Read and parse a .pen file from disk.
     */
    public static function fromFile(string $path): self
    {
        if (!file_exists($path)) {
            throw new \RuntimeException(sprintf('File not found: %s', $path));
        }

        $json = file_get_contents($path);
        if ($json === false) {
            throw new \RuntimeException(sprintf('Failed to read file: %s', $path));
        }

        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid .pen file: root must be a JSON object.');
        }

        return new self($data);
    }

    /**
     * Construct from a raw array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Create a new empty .pen document.
     */
    public static function create(int $width = 1920, int $height = 1080, string $version = '1'): self
    {
        return new self([
            'version' => $version,
            'children' => [
                [
                    'id' => self::generateId(),
                    'type' => 'frame',
                    'name' => 'Frame 1',
                    'width' => $width,
                    'height' => $height,
                    'x' => 0,
                    'y' => 0,
                    'children' => [],
                ],
            ],
        ]);
    }

    /**
     * Serialize to JSON.
     */
    public function toJson(bool $pretty = true): string
    {
        $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($this->data, $flags);
    }

    /**
     * Write the document to disk.
     */
    public function save(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory does not exist: %s', $dir));
        }

        $result = file_put_contents($path, $this->toJson());
        if ($result === false) {
            throw new \RuntimeException(sprintf('Failed to write file: %s', $path));
        }
    }

    /**
     * Validate the document against the .pen schema.
     *
     * @return list<string> Validation errors (empty if valid).
     */
    public function validate(): array
    {
        $errors = [];

        $children = $this->data['children'] ?? null;
        if ($children === null) {
            $errors[] = 'Document is missing "children" array.';
            return $errors;
        }

        if (!is_array($children)) {
            $errors[] = '"children" must be an array.';
            return $errors;
        }

        $this->validateElements($children, '', $errors);

        return $errors;
    }

    /**
     * Get top-level elements.
     *
     * @return list<array<string, mixed>>
     */
    public function getChildren(): array
    {
        return $this->data['children'] ?? [];
    }

    /**
     * Recursively find an element by ID.
     *
     * @return array<string, mixed>|null
     */
    public function findById(string $id): ?array
    {
        return $this->findByIdInElements($this->data['children'] ?? [], $id);
    }

    /**
     * Find all elements matching a type.
     *
     * @return list<array<string, mixed>>
     */
    public function findByType(string $type): array
    {
        $results = [];
        $this->collectByType($this->data['children'] ?? [], $type, $results);
        return $results;
    }

    /**
     * Extract the variables map from the document.
     *
     * @return array<string, mixed>
     */
    public function getVariables(): array
    {
        return $this->data['variables'] ?? [];
    }

    /**
     * Extract theme definitions.
     *
     * @return array<string, mixed>
     */
    public function getThemes(): array
    {
        return $this->data['themes'] ?? [];
    }

    /**
     * Find all elements with `reusable: true`.
     *
     * @return list<array<string, mixed>>
     */
    public function getComponents(): array
    {
        $results = [];
        $this->collectComponents($this->data['children'] ?? [], $results);
        return $results;
    }

    /**
     * Find all ref (instance) elements.
     *
     * @return list<array<string, mixed>>
     */
    public function getInstances(): array
    {
        return $this->findByType('ref');
    }

    /**
     * Get the raw document data.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Return a summary of the document's contents.
     *
     * @return array<string, mixed>
     */
    public function info(): array
    {
        /** @var array<string, int> $typeCounts */
        $typeCounts = [];
        $this->countTypes($this->data['children'] ?? [], $typeCounts);

        $variables = $this->getVariables();
        $themes = $this->getThemes();
        $components = $this->getComponents();
        $instances = $this->getInstances();

        return [
            'version' => $this->data['version'] ?? 'unknown',
            'elements' => array_sum($typeCounts),
            'elements_by_type' => $typeCounts,
            'variables' => count($variables),
            'themes' => count($themes),
            'components' => count($components),
            'instances' => count($instances),
        ];
    }

    // ─── Mutation Methods (return new instances) ───────────────────────

    /**
     * Add an element to a parent container (or root if no parentId).
     *
     * @param array<string, mixed> $element
     */
    public function withElement(array $element, ?string $parentId = null): self
    {
        $data = $this->data;

        if ($parentId === null) {
            $data['children'] ??= [];
            $data['children'][] = $element;
            return new self($data);
        }

        $data['children'] = $this->insertIntoParent($data['children'] ?? [], $parentId, $element);

        return new self($data);
    }

    /**
     * Update an element's properties by ID (shallow merge).
     *
     * @param array<string, mixed> $properties
     */
    public function withUpdatedElement(string $id, array $properties): self
    {
        $data = $this->data;
        $data['children'] = $this->updateInElements($data['children'] ?? [], $id, $properties);
        return new self($data);
    }

    /**
     * Remove an element by ID.
     */
    public function withoutElement(string $id): self
    {
        $data = $this->data;
        $data['children'] = $this->removeFromElements($data['children'] ?? [], $id);
        return new self($data);
    }

    /**
     * Set the variables map.
     *
     * @param array<string, mixed> $variables
     */
    public function withVariables(array $variables): self
    {
        $data = $this->data;
        $data['variables'] = $variables;
        return new self($data);
    }

    /**
     * Set a single variable.
     */
    public function withVariable(string $name, mixed $value): self
    {
        $data = $this->data;
        $data['variables'] ??= [];
        $data['variables'][$name] = $value;
        return new self($data);
    }

    /**
     * Remove a variable by name.
     */
    public function withoutVariable(string $name): self
    {
        $data = $this->data;
        unset($data['variables'][$name]);
        return new self($data);
    }

    /**
     * Set the themes definition.
     *
     * @param array<string, mixed> $themes
     */
    public function withThemes(array $themes): self
    {
        $data = $this->data;
        $data['themes'] = $themes;
        return new self($data);
    }

    /**
     * List all elements at root or within a parent, optionally filtered by type.
     *
     * @return list<array<string, mixed>>
     */
    public function listElements(?string $parentId = null, ?string $type = null): array
    {
        if ($parentId === null) {
            $elements = $this->data['children'] ?? [];
        } else {
            $parent = $this->findById($parentId);
            if ($parent === null) {
                return [];
            }
            $elements = $parent['children'] ?? [];
        }

        if ($type !== null) {
            $elements = array_values(array_filter(
                $elements,
                fn(array $el): bool => ($el['type'] ?? '') === $type,
            ));
        }

        return $elements;
    }

    /**
     * Duplicate an element with a new ID.
     *
     * @return array{document: self, element: array<string, mixed>}|null
     */
    public function copyElement(string $id, ?string $targetParentId = null): ?array
    {
        $element = $this->findById($id);
        if ($element === null) {
            return null;
        }

        $copied = $this->assignNewIds($element);
        $doc = $this->withElement($copied, $targetParentId);

        return ['document' => $doc, 'element' => $copied];
    }

    // ─── Private Helpers ───────────────────────────────────────────────

    /**
     * @param list<array<string, mixed>> $elements
     * @param list<string> $errors
     */
    private function validateElements(array $elements, string $path, array &$errors): void
    {
        foreach ($elements as $i => $element) {
            $currentPath = $path === '' ? "children[$i]" : "$path.children[$i]";

            $elementErrors = PenSchema::validateElement($element);
            foreach ($elementErrors as $err) {
                $errors[] = sprintf('%s: %s', $currentPath, $err);
            }

            if (isset($element['children'])) {
                /** @var list<array<string, mixed>> $children */
                $children = $element['children'];
                $this->validateElements($children, $currentPath, $errors);
            }
        }
    }

    /**
     * @param list<array<string, mixed>> $elements
     * @return array<string, mixed>|null
     */
    private function findByIdInElements(array $elements, string $id): ?array
    {
        foreach ($elements as $element) {
            if (($element['id'] ?? '') === $id) {
                return $element;
            }
            if (isset($element['children'])) {
                /** @var list<array<string, mixed>> $children */
                $children = $element['children'];
                $found = $this->findByIdInElements($children, $id);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        return null;
    }

    /**
     * @param list<array<string, mixed>> $elements
     * @param list<array<string, mixed>> $results
     */
    private function collectByType(array $elements, string $type, array &$results): void
    {
        foreach ($elements as $element) {
            if (($element['type'] ?? '') === $type) {
                $results[] = $element;
            }
            if (isset($element['children']) && is_array($element['children'])) {
                $this->collectByType($element['children'], $type, $results);
            }
        }
    }

    /**
     * @param list<array<string, mixed>> $elements
     * @param list<array<string, mixed>> $results
     */
    private function collectComponents(array $elements, array &$results): void
    {
        foreach ($elements as $element) {
            if (!empty($element['reusable'])) {
                $results[] = $element;
            }
            if (isset($element['children']) && is_array($element['children'])) {
                $this->collectComponents($element['children'], $results);
            }
        }
    }

    /**
     * @param list<array<string, mixed>> $elements
     * @param array<string, int> $counts
     */
    private function countTypes(array $elements, array &$counts): void
    {
        foreach ($elements as $element) {
            $type = $element['type'] ?? 'unknown';
            /** @var string $type */
            $counts[$type] = ($counts[$type] ?? 0) + 1;
            if (isset($element['children'])) {
                /** @var list<array<string, mixed>> $children */
                $children = $element['children'];
                $this->countTypes($children, $counts);
            }
        }
    }

    /**
     * @param list<array<string, mixed>> $elements
     * @param array<string, mixed> $newElement
     * @return list<array<string, mixed>>
     */
    private function insertIntoParent(array $elements, string $parentId, array $newElement): array
    {
        $result = [];
        foreach ($elements as $element) {
            if (($element['id'] ?? '') === $parentId) {
                $element['children'] ??= [];
                $element['children'][] = $newElement;
            } elseif (isset($element['children']) && is_array($element['children'])) {
                $element['children'] = $this->insertIntoParent($element['children'], $parentId, $newElement);
            }
            $result[] = $element;
        }
        return $result;
    }

    /**
     * @param list<array<string, mixed>> $elements
     * @param array<string, mixed> $properties
     * @return list<array<string, mixed>>
     */
    private function updateInElements(array $elements, string $id, array $properties): array
    {
        $result = [];
        foreach ($elements as $element) {
            if (($element['id'] ?? '') === $id) {
                $element = array_merge($element, $properties);
                // Never allow changing the ID.
                $element['id'] = $id;
            } elseif (isset($element['children']) && is_array($element['children'])) {
                $element['children'] = $this->updateInElements($element['children'], $id, $properties);
            }
            $result[] = $element;
        }
        return $result;
    }

    /**
     * @param list<array<string, mixed>> $elements
     * @return list<array<string, mixed>>
     */
    private function removeFromElements(array $elements, string $id): array
    {
        $result = [];
        foreach ($elements as $element) {
            if (($element['id'] ?? '') === $id) {
                continue;
            }
            if (isset($element['children']) && is_array($element['children'])) {
                $element['children'] = $this->removeFromElements($element['children'], $id);
            }
            $result[] = $element;
        }
        return $result;
    }

    /**
     * Deep-clone an element tree, assigning new IDs.
     *
     * @param array<string, mixed> $element
     * @return array<string, mixed>
     */
    private function assignNewIds(array $element): array
    {
        $element['id'] = self::generateId();

        if (isset($element['children']) && is_array($element['children'])) {
            $element['children'] = array_map(
                fn(array $child): array => $this->assignNewIds($child),
                $element['children'],
            );
        }

        return $element;
    }

    /**
     * Generate a unique element ID compatible with the .pen format.
     */
    public static function generateId(): string
    {
        return bin2hex(random_bytes(8));
    }
}
