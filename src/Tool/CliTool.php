<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiToolkitPencilDev\Tool;

use CarmeloSantana\PHPAgents\Contract\ToolInterface;
use CarmeloSantana\PHPAgents\Tool\Tool;
use CarmeloSantana\PHPAgents\Tool\ToolResult;
use CarmeloSantana\PHPAgents\Tool\Parameter\EnumParameter;
use CarmeloSantana\PHPAgents\Tool\Parameter\StringParameter;

/**
 * Pencil CLI wrapper for agent-config batch operations and status checks.
 */
final readonly class CliTool
{
    public function __construct(
        private string $workspacePath,
    ) {}

    public function build(): ToolInterface
    {
        return new Tool(
            name: 'pencil_cli',
            description: 'Interact with the Pencil CLI — check if Pencil is installed, run CLI commands, or generate batch agent-config JSON for design generation.',
            parameters: [
                new EnumParameter(
                    'action',
                    'CLI operation to perform.',
                    values: ['status', 'run', 'batch'],
                    required: true,
                ),
                new StringParameter(
                    'command',
                    'CLI command arguments to pass to `pencil` (for run action). Example: "--agent-config config.json"',
                    required: false,
                ),
                new StringParameter(
                    'config',
                    'JSON array of batch entries for agent-config (for batch action). Each entry: {"file": "output.pen", "prompt": "Design description", "model": "optional/model-name", "attachments": ["optional-file.png"]}',
                    required: false,
                ),
                new StringParameter(
                    'output_path',
                    'Path to write the generated agent-config JSON (for batch action, default: agent-config.json).',
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
            'status' => $this->status(),
            'run' => $this->run($args),
            'batch' => $this->batch($args),
            default => ToolResult::error("Unknown action: {$action}"),
        };
    }

    private function status(): ToolResult
    {
        $pencilPath = $this->findPencilBinary();

        if ($pencilPath === null) {
            return ToolResult::success(
                "Pencil CLI: NOT FOUND\n\n" .
                "The Pencil CLI is not installed or not in PATH.\n" .
                "Note: The headless CLI (npm package) is coming soon.\n" .
                "Currently the CLI is bundled with the Pencil desktop app.\n\n" .
                "You can still use pencil_document, pencil_design, pencil_component, pencil_variable, and pencil_export tools " .
                "to manipulate .pen files directly without the CLI.",
            );
        }

        // Try to get version.
        $output = [];
        $exitCode = 0;
        exec(escapeshellarg($pencilPath) . ' --version 2>&1', $output, $exitCode);

        $version = $exitCode === 0 ? implode("\n", $output) : 'unknown';

        return ToolResult::success(sprintf(
            "Pencil CLI: FOUND\nPath: %s\nVersion: %s",
            $pencilPath,
            $version,
        ));
    }

    /**
     * @param array<string, mixed> $args
     */
    private function run(array $args): ToolResult
    {
        $command = trim((string) ($args['command'] ?? ''));
        if ($command === '') {
            return ToolResult::error('The "command" parameter is required for run.');
        }

        $pencilPath = $this->findPencilBinary();
        if ($pencilPath === null) {
            return ToolResult::error('Pencil CLI not found. Install the Pencil desktop app or wait for the headless npm package.');
        }

        // Only allow safe argument patterns — no shell metacharacters.
        if (preg_match('/[;&|`$(){}]/', $command)) {
            return ToolResult::error('Shell metacharacters are not allowed in the command parameter.');
        }

        $fullCommand = escapeshellarg($pencilPath) . ' ' . $command;
        $output = [];
        $exitCode = 0;
        exec($fullCommand . ' 2>&1', $output, $exitCode);

        $result = implode("\n", $output);

        if ($exitCode !== 0) {
            return ToolResult::error(sprintf("Pencil CLI exited with code %d:\n%s", $exitCode, $result));
        }

        return ToolResult::success($result !== '' ? $result : 'Command completed successfully.');
    }

    /**
     * @param array<string, mixed> $args
     */
    private function batch(array $args): ToolResult
    {
        $configJson = trim((string) ($args['config'] ?? ''));
        if ($configJson === '') {
            return ToolResult::error('The "config" parameter is required for batch. Provide a JSON array of batch entries.');
        }

        try {
            $entries = json_decode($configJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return ToolResult::error(sprintf('Invalid config JSON: %s', $e->getMessage()));
        }

        if (!is_array($entries) || $entries === []) {
            return ToolResult::error('Config must be a non-empty JSON array of batch entries.');
        }

        // Validate and normalize entries.
        $normalized = [];
        foreach ($entries as $i => $entry) {
            if (!is_array($entry)) {
                return ToolResult::error(sprintf('Entry %d must be a JSON object.', $i));
            }

            $file = trim((string) ($entry['file'] ?? ''));
            $prompt = trim((string) ($entry['prompt'] ?? ''));

            if ($file === '') {
                return ToolResult::error(sprintf('Entry %d is missing required "file" field.', $i));
            }
            if ($prompt === '') {
                return ToolResult::error(sprintf('Entry %d is missing required "prompt" field.', $i));
            }

            $norm = [
                'file' => $file,
                'prompt' => $prompt,
            ];

            $model = trim((string) ($entry['model'] ?? ''));
            if ($model !== '') {
                $norm['model'] = $model;
            }

            $attachments = $entry['attachments'] ?? [];
            if (is_array($attachments) && $attachments !== []) {
                $norm['attachments'] = $attachments;
            }

            $normalized[] = $norm;
        }

        // Write agent-config JSON.
        $outputPath = trim((string) ($args['output_path'] ?? ''));
        if ($outputPath === '') {
            $outputPath = 'agent-config.json';
        }
        if (!str_starts_with($outputPath, '/')) {
            $outputPath = rtrim($this->workspacePath, '/') . '/' . $outputPath;
        }

        $dir = dirname($outputPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return ToolResult::error(sprintf('Failed to create directory: %s', $dir));
        }

        $json = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $written = file_put_contents($outputPath, $json);
        if ($written === false) {
            return ToolResult::error(sprintf('Failed to write config: %s', $outputPath));
        }

        $relativePath = str_starts_with($outputPath, rtrim($this->workspacePath, '/'))
            ? substr($outputPath, strlen(rtrim($this->workspacePath, '/') . '/'))
            : $outputPath;

        return ToolResult::success(sprintf(
            "Generated agent-config with %d entries: %s\n\nTo run: pencil --agent-config %s",
            count($normalized),
            $relativePath,
            $relativePath,
        ));
    }

    private function findPencilBinary(): ?string
    {
        // Check common locations.
        $candidates = [
            '/usr/local/bin/pencil',
            '/usr/bin/pencil',
        ];

        // Also check PATH via which.
        $output = [];
        $exitCode = 0;
        exec('which pencil 2>/dev/null', $output, $exitCode);
        if ($exitCode === 0 && isset($output[0])) {
            return trim($output[0]);
        }

        foreach ($candidates as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        // macOS app bundle location.
        $macApp = '/Applications/Pencil.app/Contents/MacOS/pencil';
        if (is_executable($macApp)) {
            return $macApp;
        }

        return null;
    }
}
