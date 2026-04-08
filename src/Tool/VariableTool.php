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
 * Design token and theme management in .pen documents.
 */
final readonly class VariableTool
{
    public function __construct(
        private string $workspacePath,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'pencil_variable',
            description: 'Manage design variables (tokens) and themes in a Pencil .pen document — list, get, set, delete variables, import/export CSS custom properties, or configure themes.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Variable operation to perform.',
                    values: ['list', 'get', 'set', 'delete', 'import_css', 'export_css', 'set_theme'],
                    required: true,
                ),
                new StringParameter(
                    'path',
                    'Path to the .pen file (relative to workspace).',
                    required: true,
                ),
                new StringParameter(
                    'name',
                    'Variable name (required for get, set, delete).',
                    required: false,
                ),
                new EnumParameter(
                    'type',
                    'Variable type (required for set when creating new variable).',
                    values: PenSchema::VARIABLE_TYPES,
                    required: false,
                ),
                new StringParameter(
                    'value',
                    'Variable value (required for set). For themed values, provide a JSON object mapping theme keys to values.',
                    required: false,
                ),
                new StringParameter(
                    'theme',
                    'JSON object for theme configuration (for set_theme). Example: {"mode": {"Light": true, "Dark": false}}',
                    required: false,
                ),
                new StringParameter(
                    'css',
                    'CSS text to import variables from (for import_css). Parses :root { --name: value; } blocks.',
                    required: false,
                ),
                new StringParameter(
                    'css_path',
                    'Path to CSS file to import from (for import_css, alternative to css parameter).',
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
            'list' => $this->listVariables($args),
            'get' => $this->getVariable($args),
            'set' => $this->setVariable($args),
            'delete' => $this->deleteVariable($args),
            'import_css' => $this->importCss($args),
            'export_css' => $this->exportCss($args),
            'set_theme' => $this->setTheme($args),
            default => ToolResult::error("Unknown action: {$action}"),
        };
    }

    /**
     * @param array<string, mixed> $args
     */
    private function listVariables(array $args): ToolResult
    {
        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [, $doc] = $pathResult;

        $variables = $doc->getVariables();
        $themes = $doc->getThemes();

        if ($variables === []) {
            return ToolResult::success('No variables defined in this document.');
        }

        $lines = [];
        foreach ($variables as $name => $var) {
            if (is_array($var)) {
                $type = $var['type'] ?? 'unknown';
                $value = $var['value'] ?? '';
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_SLASHES);
                }
                $lines[] = sprintf('  $%s (%s) = %s', $name, $type, $value);
            } else {
                $lines[] = sprintf('  $%s = %s', $name, (string) $var);
            }
        }

        $themeSummary = '';
        if ($themes !== []) {
            $themeSummary = sprintf("\n\nThemes: %s", implode(', ', array_keys($themes)));
        }

        return ToolResult::success(sprintf(
            "%d variable(s):\n%s%s",
            count($variables),
            implode("\n", $lines),
            $themeSummary,
        ));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function getVariable(array $args): ToolResult
    {
        $name = $this->requireName($args);
        if ($name instanceof ToolResult) {
            return $name;
        }

        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [, $doc] = $pathResult;

        $variables = $doc->getVariables();
        if (!isset($variables[$name])) {
            return ToolResult::error(sprintf('Variable "$%s" not found.', $name));
        }

        $var = $variables[$name];

        return ToolResult::success(sprintf(
            "Variable $%s:\n%s",
            $name,
            json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function setVariable(array $args): ToolResult
    {
        $name = $this->requireName($args);
        if ($name instanceof ToolResult) {
            return $name;
        }

        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [$path, $doc] = $pathResult;

        $variables = $doc->getVariables();
        $isNew = !isset($variables[$name]);

        $type = trim((string) ($args['type'] ?? ''));
        $valueRaw = trim((string) ($args['value'] ?? ''));

        if ($isNew && $type === '') {
            return ToolResult::error('The "type" parameter is required when creating a new variable. Valid types: color, number, string, boolean.');
        }

        // Use existing type if not provided.
        if ($type === '' && isset($variables[$name]) && is_array($variables[$name])) {
            $type = $variables[$name]['type'] ?? 'string';
        }

        if ($valueRaw === '') {
            return ToolResult::error('The "value" parameter is required for set.');
        }

        // Try to parse value as JSON for themed/structured values.
        $value = $this->parseValue($valueRaw, $type);

        $varDef = [
            'type' => $type,
            'value' => $value,
        ];

        // Preserve existing themed values if we're updating.
        if (!$isNew && is_array($variables[$name]) && isset($variables[$name]['themes'])) {
            $varDef['themes'] = $variables[$name]['themes'];
        }

        $doc = $doc->withVariable($name, $varDef);

        try {
            $doc->save($path);
        } catch (\RuntimeException $e) {
            return ToolResult::error($e->getMessage());
        }

        $action = $isNew ? 'Created' : 'Updated';
        return ToolResult::success(sprintf('%s variable $%s (%s) = %s', $action, $name, $type, $valueRaw));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function deleteVariable(array $args): ToolResult
    {
        $name = $this->requireName($args);
        if ($name instanceof ToolResult) {
            return $name;
        }

        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [$path, $doc] = $pathResult;

        $variables = $doc->getVariables();
        if (!isset($variables[$name])) {
            return ToolResult::error(sprintf('Variable "$%s" not found.', $name));
        }

        $doc = $doc->withoutVariable($name);

        try {
            $doc->save($path);
        } catch (\RuntimeException $e) {
            return ToolResult::error($e->getMessage());
        }

        return ToolResult::success(sprintf('Deleted variable $%s.', $name));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function importCss(array $args): ToolResult
    {
        // Get CSS content from inline text or file.
        $css = trim((string) ($args['css'] ?? ''));
        $cssPath = trim((string) ($args['css_path'] ?? ''));

        if ($css === '' && $cssPath !== '') {
            if (!str_starts_with($cssPath, '/')) {
                $cssPath = rtrim($this->workspacePath, '/') . '/' . $cssPath;
            }
            if (!file_exists($cssPath)) {
                return ToolResult::error(sprintf('CSS file not found: %s', $cssPath));
            }
            $cssContent = file_get_contents($cssPath);
            if ($cssContent === false) {
                return ToolResult::error(sprintf('Failed to read CSS file: %s', $cssPath));
            }
            $css = $cssContent;
        }

        if ($css === '') {
            return ToolResult::error('Provide CSS text via "css" parameter or a file path via "css_path" parameter.');
        }

        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [$path, $doc] = $pathResult;

        // Parse CSS custom properties from :root blocks.
        $parsed = $this->parseCssVariables($css);

        if ($parsed === []) {
            return ToolResult::error('No CSS custom properties found. Expected format: :root { --name: value; }');
        }

        foreach ($parsed as $name => $value) {
            $type = $this->inferVariableType($value);
            $doc = $doc->withVariable($name, [
                'type' => $type,
                'value' => $this->coerceValue($value, $type),
            ]);
        }

        try {
            $doc->save($path);
        } catch (\RuntimeException $e) {
            return ToolResult::error($e->getMessage());
        }

        return ToolResult::success(sprintf(
            "Imported %d CSS variable(s):\n%s",
            count($parsed),
            implode("\n", array_map(
                fn(string $n, string $v): string => sprintf('  $%s = %s', $n, $v),
                array_keys($parsed),
                array_values($parsed),
            )),
        ));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function exportCss(array $args): ToolResult
    {
        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [, $doc] = $pathResult;

        $variables = $doc->getVariables();

        if ($variables === []) {
            return ToolResult::success('No variables to export.');
        }

        $lines = [':root {'];
        foreach ($variables as $name => $var) {
            $value = is_array($var) ? ($var['value'] ?? '') : $var;
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES);
            }
            $cssName = $this->toCssName($name);
            $lines[] = sprintf('  --%s: %s;', $cssName, $value);
        }
        $lines[] = '}';

        $output = implode("\n", $lines);

        return ToolResult::success($output);
    }

    /**
     * @param array<string, mixed> $args
     */
    private function setTheme(array $args): ToolResult
    {
        $themeJson = trim((string) ($args['theme'] ?? ''));
        if ($themeJson === '') {
            return ToolResult::error('The "theme" parameter is required for set_theme. Provide a JSON object defining theme axes, e.g. {"mode": {"Light": true, "Dark": false}}');
        }

        try {
            $themes = json_decode($themeJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return ToolResult::error(sprintf('Invalid theme JSON: %s', $e->getMessage()));
        }

        if (!is_array($themes)) {
            return ToolResult::error('Theme must be a JSON object.');
        }

        $pathResult = $this->loadDocument($args);
        if ($pathResult instanceof ToolResult) {
            return $pathResult;
        }
        [$path, $doc] = $pathResult;

        $doc = $doc->withThemes($themes);

        try {
            $doc->save($path);
        } catch (\RuntimeException $e) {
            return ToolResult::error($e->getMessage());
        }

        $axes = array_keys($themes);

        return ToolResult::success(sprintf(
            "Updated themes with %d axis/axes: %s",
            count($axes),
            implode(', ', $axes),
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
     */
    private function requireName(array $args): string|ToolResult
    {
        $name = trim((string) ($args['name'] ?? ''));
        if ($name === '') {
            return ToolResult::error('The "name" parameter is required.');
        }
        // Strip leading $ if the user includes it.
        return ltrim($name, '$');
    }

    /**
     * Parse CSS custom properties from CSS text.
     *
     * @return array<string, string>
     */
    private function parseCssVariables(string $css): array
    {
        $vars = [];

        // Match properties inside :root { ... } blocks.
        if (preg_match_all('/:root\s*\{([^}]+)\}/s', $css, $rootMatches)) {
            foreach ($rootMatches[1] as $block) {
                if (preg_match_all('/--([a-zA-Z0-9_-]+)\s*:\s*([^;]+);/', $block, $propMatches, PREG_SET_ORDER)) {
                    foreach ($propMatches as $match) {
                        $name = $this->fromCssName($match[1]);
                        $value = trim($match[2]);
                        $vars[$name] = $value;
                    }
                }
            }
        }

        return $vars;
    }

    /**
     * Convert CSS property name (kebab-case) to Pencil variable name (camelCase).
     */
    private function fromCssName(string $cssName): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $cssName))));
    }

    /**
     * Convert Pencil variable name (camelCase) to CSS property name (kebab-case).
     */
    private function toCssName(string $name): string
    {
        return strtolower((string) preg_replace('/[A-Z]/', '-$0', $name));
    }

    /**
     * Infer the Pencil variable type from a CSS value.
     */
    private function inferVariableType(string $value): string
    {
        // Color values: hex, rgb(), hsl().
        if (str_starts_with($value, '#') || str_starts_with($value, 'rgb') || str_starts_with($value, 'hsl')) {
            return 'color';
        }

        // Boolean.
        if (in_array(strtolower($value), ['true', 'false'], true)) {
            return 'boolean';
        }

        // Numeric (including units like px, rem, etc.).
        if (is_numeric($value) || preg_match('/^-?\d+(\.\d+)?(px|rem|em|%|vh|vw)$/', $value)) {
            return 'number';
        }

        return 'string';
    }

    /**
     * Coerce a CSS string value to the appropriate PHP type.
     */
    private function coerceValue(string $value, string $type): string|int|float|bool
    {
        return match ($type) {
            'number' => is_numeric($value) ? (str_contains($value, '.') ? (float) $value : (int) $value) : $value,
            'boolean' => strtolower($value) === 'true',
            default => $value,
        };
    }

    /**
     * Parse a value string for variable set operations.
     */
    private function parseValue(string $raw, string $type): mixed
    {
        // Attempt JSON decode for complex/themed values.
        if (str_starts_with($raw, '{') || str_starts_with($raw, '[')) {
            try {
                return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                // Fall through to type coercion.
            }
        }

        return $this->coerceValue($raw, $type);
    }
}
