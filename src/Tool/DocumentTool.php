<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiToolkitPencilDev\Tool;

use CarmeloSantana\PHPAgents\Contract\ToolInterface;
use CarmeloSantana\PHPAgents\Tool\Tool;
use CarmeloSantana\PHPAgents\Tool\ToolResult;
use CarmeloSantana\PHPAgents\Tool\Parameter\EnumParameter;
use CarmeloSantana\PHPAgents\Tool\Parameter\NumberParameter;
use CarmeloSantana\PHPAgents\Tool\Parameter\StringParameter;
use CarmeloSantana\CoquiToolkitPencilDev\PenDocument;

/**
 * .pen file lifecycle: create, read, list, validate, info.
 */
final readonly class DocumentTool
{
    public function __construct(
        private string $workspacePath,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'pencil_document',
            description: 'Manage Pencil .pen design documents — create new documents, read existing ones, list all .pen files, validate structure, or get document info/summary.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Document operation to perform.',
                    values: ['create', 'read', 'list', 'validate', 'info'],
                    required: true,
                ),
                new StringParameter(
                    'path',
                    'Path to the .pen file (relative to workspace). Required for create, read, validate, info.',
                    required: false,
                ),
                new NumberParameter(
                    'width',
                    'Canvas width in pixels (optional for create, default: 1920).',
                    required: false,
                ),
                new NumberParameter(
                    'height',
                    'Canvas height in pixels (optional for create, default: 1080).',
                    required: false,
                ),
                new StringParameter(
                    'variables',
                    'JSON object of initial variables to set (optional for create). Example: {"primary": {"type": "color", "value": "#3B82F6"}}',
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
            'read' => $this->read($args),
            'list' => $this->listFiles(),
            'validate' => $this->validate($args),
            'info' => $this->info($args),
            default => ToolResult::error("Unknown action: {$action}"),
        };
    }

    /**
     * @param array<string, mixed> $args
     */
    private function create(array $args): ToolResult
    {
        $path = $this->resolvePath($args);
        if ($path === null) {
            return ToolResult::error('The "path" parameter is required for create. Provide a .pen filename.');
        }

        if (!str_ends_with($path, '.pen')) {
            $path .= '.pen';
        }

        $width = (int) ($args['width'] ?? 1920);
        $height = (int) ($args['height'] ?? 1080);

        if ($width <= 0) {
            $width = 1920;
        }
        if ($height <= 0) {
            $height = 1080;
        }

        $doc = PenDocument::create($width, $height);

        // Apply initial variables if provided.
        $variablesJson = trim((string) ($args['variables'] ?? ''));
        if ($variablesJson !== '') {
            try {
                $variables = json_decode($variablesJson, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($variables)) {
                    $doc = $doc->withVariables($variables);
                }
            } catch (\JsonException $e) {
                return ToolResult::error(sprintf('Invalid variables JSON: %s', $e->getMessage()));
            }
        }

        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return ToolResult::error(sprintf('Failed to create directory: %s', $dir));
        }

        try {
            $doc->save($path);
        } catch (\RuntimeException $e) {
            return ToolResult::error($e->getMessage());
        }

        $info = $doc->info();

        return ToolResult::success(sprintf(
            "Created .pen document: %s\nDimensions: %dx%d\nVariables: %d",
            $this->relativePath($path),
            $width,
            $height,
            $info['variables'],
        ));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function read(array $args): ToolResult
    {
        $path = $this->resolvePath($args);
        if ($path === null) {
            return ToolResult::error('The "path" parameter is required for read.');
        }

        try {
            $doc = PenDocument::fromFile($path);
        } catch (\RuntimeException|\JsonException $e) {
            return ToolResult::error($e->getMessage());
        }

        $info = $doc->info();
        $children = $doc->getChildren();

        $tree = $this->buildElementTree($children, 0);

        $output = sprintf(
            "Document: %s\nVersion: %s\nTotal elements: %d\nVariables: %d | Themes: %d | Components: %d | Instances: %d\n\nElement tree:\n%s",
            $this->relativePath($path),
            $info['version'],
            $info['elements'],
            $info['variables'],
            $info['themes'],
            $info['components'],
            $info['instances'],
            $tree,
        );

        return ToolResult::success($output);
    }

    private function listFiles(): ToolResult
    {
        $files = $this->findPenFiles($this->workspacePath);

        if ($files === []) {
            return ToolResult::success('No .pen files found in workspace.');
        }

        $lines = array_map(
            fn(string $f): string => '  ' . $this->relativePath($f),
            $files,
        );

        return ToolResult::success(sprintf("Found %d .pen file(s):\n%s", count($files), implode("\n", $lines)));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function validate(array $args): ToolResult
    {
        $path = $this->resolvePath($args);
        if ($path === null) {
            return ToolResult::error('The "path" parameter is required for validate.');
        }

        try {
            $doc = PenDocument::fromFile($path);
        } catch (\RuntimeException|\JsonException $e) {
            return ToolResult::error($e->getMessage());
        }

        $errors = $doc->validate();

        if ($errors === []) {
            return ToolResult::success(sprintf('Document "%s" is valid.', $this->relativePath($path)));
        }

        $numbered = array_map(
            fn(int $i, string $err): string => sprintf('  %d. %s', $i + 1, $err),
            array_keys($errors),
            $errors,
        );

        return ToolResult::error(sprintf(
            "Document \"%s\" has %d validation error(s):\n%s",
            $this->relativePath($path),
            count($errors),
            implode("\n", $numbered),
        ));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function info(array $args): ToolResult
    {
        $path = $this->resolvePath($args);
        if ($path === null) {
            return ToolResult::error('The "path" parameter is required for info.');
        }

        try {
            $doc = PenDocument::fromFile($path);
        } catch (\RuntimeException|\JsonException $e) {
            return ToolResult::error($e->getMessage());
        }

        $info = $doc->info();

        $typeSummary = '';
        if ($info['elements_by_type'] !== []) {
            $typeSummary = "\nElements by type:";
            foreach ($info['elements_by_type'] as $type => $count) {
                $typeSummary .= sprintf("\n  %s: %d", $type, $count);
            }
        }

        $variables = $doc->getVariables();
        $varSummary = '';
        if ($variables !== []) {
            $varSummary = "\nVariables:";
            foreach ($variables as $name => $var) {
                $type = is_array($var) ? ($var['type'] ?? 'unknown') : 'unknown';
                $varSummary .= sprintf("\n  $%s (%s)", $name, $type);
            }
        }

        $components = $doc->getComponents();
        $compSummary = '';
        if ($components !== []) {
            $compSummary = "\nComponents:";
            foreach ($components as $comp) {
                $compSummary .= sprintf("\n  %s (%s)", $comp['name'] ?? $comp['id'], $comp['type']);
            }
        }

        return ToolResult::success(sprintf(
            "Document: %s\nVersion: %s\nTotal elements: %d%s%s%s",
            $this->relativePath($path),
            $info['version'],
            $info['elements'],
            $typeSummary,
            $varSummary,
            $compSummary,
        ));
    }

    // ─── Private Helpers ───────────────────────────────────────────────

    /**
     * @param array<string, mixed> $args
     */
    private function resolvePath(array $args): ?string
    {
        $path = trim((string) ($args['path'] ?? ''));
        if ($path === '') {
            return null;
        }

        // Already absolute.
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return rtrim($this->workspacePath, '/') . '/' . $path;
    }

    private function relativePath(string $path): string
    {
        $prefix = rtrim($this->workspacePath, '/') . '/';
        if (str_starts_with($path, $prefix)) {
            return substr($path, strlen($prefix));
        }
        return $path;
    }

    /**
     * @return list<string>
     */
    private function findPenFiles(string $dir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->getExtension() === 'pen') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);
        return $files;
    }

    /**
     * Build an indented element tree string.
     *
     * @param list<array<string, mixed>> $elements
     */
    private function buildElementTree(array $elements, int $depth): string
    {
        $lines = [];
        $indent = str_repeat('  ', $depth);

        foreach ($elements as $element) {
            $type = $element['type'] ?? 'unknown';
            $id = $element['id'] ?? '?';
            $name = $element['name'] ?? '';
            $label = $name !== '' ? sprintf('%s "%s" [%s]', $type, $name, $id) : sprintf('%s [%s]', $type, $id);

            // Add dimensions for visual elements.
            $w = $element['width'] ?? null;
            $h = $element['height'] ?? null;
            if ($w !== null && $h !== null) {
                $label .= sprintf(' %dx%d', $w, $h);
            }

            $lines[] = $indent . '- ' . $label;

            if (isset($element['children']) && is_array($element['children'])) {
                $lines[] = $this->buildElementTree($element['children'], $depth + 1);
            }
        }

        return implode("\n", $lines);
    }
}
