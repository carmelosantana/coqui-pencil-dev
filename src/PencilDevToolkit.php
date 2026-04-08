<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiToolkitPencilDev;

use CarmeloSantana\PHPAgents\Contract\ToolkitInterface;
use CarmeloSantana\CoquiToolkitPencilDev\Tool\CliTool;
use CarmeloSantana\CoquiToolkitPencilDev\Tool\ComponentTool;
use CarmeloSantana\CoquiToolkitPencilDev\Tool\DesignTool;
use CarmeloSantana\CoquiToolkitPencilDev\Tool\DocumentTool;
use CarmeloSantana\CoquiToolkitPencilDev\Tool\ExportTool;
use CarmeloSantana\CoquiToolkitPencilDev\Tool\VariableTool;

/**
 * Pencil.dev design toolkit for Coqui.
 *
 * Provides direct .pen file manipulation (JSON-based design documents),
 * reusable component management, design token/theme systems, code export,
 * and Pencil CLI integration.
 */
final readonly class PencilDevToolkit implements ToolkitInterface
{
    public function __construct(
        private string $workspacePath = '',
    ) {}

    public function tools(): array
    {
        return [
            (new DocumentTool($this->workspacePath))->build(),
            (new DesignTool($this->workspacePath))->build(),
            (new ComponentTool($this->workspacePath))->build(),
            (new VariableTool($this->workspacePath))->build(),
            (new ExportTool($this->workspacePath))->build(),
            (new CliTool($this->workspacePath))->build(),
        ];
    }

    public function guidelines(): string
    {
        return <<<'GUIDELINES'
        ## Pencil.dev Design Toolkit

        This toolkit provides tools to create, manipulate, and export Pencil.dev design documents (`.pen` files). Pencil is a design tool where `.pen` files are JSON documents — no API keys or running application needed.

        ### Available Tools

        | Tool | Purpose |
        |------|---------|
        | `pencil_document` | Create, read, list, validate, and get info on `.pen` files |
        | `pencil_design` | Insert, get, update, move, copy, and delete design elements |
        | `pencil_component` | Create reusable components and instantiate them as `ref` elements |
        | `pencil_variable` | Manage design variables (tokens), themes, and CSS import/export |
        | `pencil_export` | Export designs to HTML/CSS, React/Tailwind, SVG, or JSON |
        | `pencil_cli` | Check Pencil CLI status, run commands, generate batch configs |

        ### .pen Format Overview

        A `.pen` file is a JSON document with a `children` array of elements. Each element has:
        - `id` — unique identifier
        - `type` — one of: `rectangle`, `ellipse`, `line`, `polygon`, `path`, `text`, `frame`, `group`, `ref`, `icon_font`, `note`, `prompt`, `context`
        - `x`, `y` — position coordinates
        - `width`, `height` — dimensions (most types)
        - `fills` — array of fill objects (`{type: "color", color: "#hex"}`)
        - `strokes` — array of stroke objects
        - `children` — nested elements (only `frame` and `group`)

        **Containers**: `frame` and `group` can hold children. Frames support flexbox layout (`layout: "vertical"|"horizontal"`).

        **Components**: Any element with `reusable: true` becomes a component. Use `ref` elements to create instances that reference the component and optionally override properties via `descendants`.

        **Variables**: Design tokens stored in the document's `variables` object. Referenced in element properties with `$variableName`. Support themed values.

        ### Workflow: Create a New Design

        1. `pencil_document(action: "create", path: "my-design.pen", width: 1440, height: 900)`
        2. Use `pencil_design(action: "list", path: "my-design.pen")` to see the root frame
        3. `pencil_design(action: "insert", path: "my-design.pen", type: "rectangle", parent_id: "<frame_id>", properties: '{"width": 200, "height": 100, "fills": [{"type": "color", "color": "#3B82F6"}]}')`
        4. Export: `pencil_export(action: "html", path: "my-design.pen", output_path: "design.html")`

        ### Workflow: Build a Component Library

        1. Create base elements with `pencil_design(action: "insert", ...)`
        2. Mark as reusable: `pencil_component(action: "create", path: "...", component_id: "<id>")`
        3. Instantiate: `pencil_component(action: "instantiate", path: "...", component_id: "<id>", descendants: '{"child_id": {"content": "Custom text"}}')`

        ### Workflow: Sync CSS Variables

        1. Import: `pencil_variable(action: "import_css", path: "...", css_path: "styles.css")` — converts CSS custom properties to Pencil variables
        2. Export: `pencil_variable(action: "export_css", path: "...")` — generates `:root { --var: value; }` CSS

        ### Workflow: Batch Design Generation

        Use `pencil_cli(action: "batch", config: '[{"file": "page1.pen", "prompt": "Landing page hero section"}, {"file": "page2.pen", "prompt": "Pricing table"}]')` to generate an agent-config JSON for Pencil's CLI batch mode.

        ### MCP Live Preview

        For live preview and screenshots when Pencil desktop is running, use the `mcp_client` toolkit to connect to Pencil's MCP server. Available MCP tools:
        - `batch_design` — generate designs from prompts
        - `batch_get` — retrieve design data
        - `get_screenshot` — capture design screenshots
        - `snapshot_layout` — get layout snapshot
        - `get_editor_state` — current editor state
        - `get_variables` / `set_variables` — live variable management

        ### Complementary Toolkits

        - **Browser toolkit**: Preview exported HTML files
        - **Webserver toolkit**: Serve exported files for browser preview
        - **Code-Edit toolkit**: Modify generated React/HTML code
        - **MCP Client toolkit**: Connect to Pencil MCP server for live interaction

        ### Reference

        - Pencil docs: https://docs.pencil.dev
        - .pen format: https://docs.pencil.dev/for-developers/the-pen-format
        - CLI reference: https://docs.pencil.dev/for-developers/pencil-cli
        - AI integration: https://docs.pencil.dev/getting-started/ai-integration
        GUIDELINES;
    }
}
