<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiToolkitPencilDev\Tool;

use CarmeloSantana\PHPAgents\Contract\ToolInterface;
use CarmeloSantana\PHPAgents\Tool\Tool;
use CarmeloSantana\PHPAgents\Tool\ToolResult;
use CarmeloSantana\PHPAgents\Tool\Parameter\EnumParameter;
use CarmeloSantana\PHPAgents\Tool\Parameter\StringParameter;
use CarmeloSantana\CoquiToolkitPencilDev\Export\HtmlExporter;
use CarmeloSantana\CoquiToolkitPencilDev\Export\ReactExporter;
use CarmeloSantana\CoquiToolkitPencilDev\Export\SvgExporter;
use CarmeloSantana\CoquiToolkitPencilDev\PenDocument;

/**
 * Export .pen documents to code formats (HTML, React, SVG, JSON).
 */
final readonly class ExportTool
{
    public function __construct(
        private string $workspacePath,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'pencil_export',
            description: 'Export a Pencil .pen document to code — HTML/CSS, React/JSX with Tailwind, SVG, or clean JSON. Optionally export a single element by ID.',
            parameters: [
                new EnumParameter(
                    'action',
                    'Export format.',
                    values: ['html', 'react', 'svg', 'json'],
                    required: true,
                ),
                new StringParameter(
                    'path',
                    'Path to the .pen file (relative to workspace).',
                    required: true,
                ),
                new StringParameter(
                    'element_id',
                    'Export only this element and its children (optional — defaults to entire document).',
                    required: false,
                ),
                new StringParameter(
                    'output_path',
                    'Path to write the output file (relative to workspace). If omitted, returns the output as text.',
                    required: false,
                ),
                new StringParameter(
                    'component_name',
                    'React component name (for react export, default: "Design").',
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

        // Load the document.
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

        // Resolve elements to export.
        $elementId = trim((string) ($args['element_id'] ?? ''));
        if ($elementId !== '') {
            $element = $doc->findById($elementId);
            if ($element === null) {
                return ToolResult::error(sprintf('Element "%s" not found.', $elementId));
            }
            $elements = [$element];
        } else {
            $elements = $doc->getChildren();
        }

        $variables = $doc->getVariables();

        $output = match ($action) {
            'html' => $this->exportHtml($elements, $variables),
            'react' => $this->exportReact($elements, $variables, $args),
            'svg' => $this->exportSvg($elements, $variables),
            'json' => $this->exportJson($elements),
            default => null,
        };

        if ($output === null) {
            return ToolResult::error("Unknown export format: {$action}");
        }

        // Write to file if output_path is specified.
        $outputPath = trim((string) ($args['output_path'] ?? ''));
        if ($outputPath !== '') {
            if (!str_starts_with($outputPath, '/')) {
                $outputPath = rtrim($this->workspacePath, '/') . '/' . $outputPath;
            }

            $dir = dirname($outputPath);
            if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
                return ToolResult::error(sprintf('Failed to create directory: %s', $dir));
            }

            $written = file_put_contents($outputPath, $output);
            if ($written === false) {
                return ToolResult::error(sprintf('Failed to write file: %s', $outputPath));
            }

            $relativePath = str_starts_with($outputPath, rtrim($this->workspacePath, '/'))
                ? substr($outputPath, strlen(rtrim($this->workspacePath, '/') . '/'))
                : $outputPath;

            return ToolResult::success(sprintf(
                "Exported %s to %s (%d bytes)",
                $action,
                $relativePath,
                $written,
            ));
        }

        return ToolResult::success($output);
    }

    /**
     * @param list<array<string, mixed>> $elements
     * @param array<string, mixed> $variables
     */
    private function exportHtml(array $elements, array $variables): string
    {
        return (new HtmlExporter())->export($elements, $variables);
    }

    /**
     * @param list<array<string, mixed>> $elements
     * @param array<string, mixed> $variables
     * @param array<string, mixed> $args
     */
    private function exportReact(array $elements, array $variables, array $args): string
    {
        $componentName = trim((string) ($args['component_name'] ?? ''));
        if ($componentName === '') {
            $componentName = 'Design';
        }

        return (new ReactExporter())->export($elements, $variables, $componentName);
    }

    /**
     * @param list<array<string, mixed>> $elements
     * @param array<string, mixed> $variables
     */
    private function exportSvg(array $elements, array $variables): string
    {
        return (new SvgExporter())->export($elements, $variables);
    }

    /**
     * @param list<array<string, mixed>> $elements
     */
    private function exportJson(array $elements): string
    {
        return json_encode($elements, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
