# Pencil.dev Toolkit for Coqui

A Coqui toolkit for creating, manipulating, and exporting [Pencil.dev](https://pencil.dev) design documents (`.pen` files). Provides direct JSON-based document manipulation with no API keys or running application needed.

## Features

- **Document management** — create, read, list, validate `.pen` files
- **Element CRUD** — insert, update, move, copy, delete design elements (rectangles, ellipses, text, frames, groups, etc.)
- **Reusable components** — mark elements as components, create instances with descendant overrides
- **Design tokens** — manage variables (color, number, string, boolean) with themed values
- **CSS sync** — import CSS custom properties into Pencil variables and export back
- **Code export** — generate HTML/CSS, React/Tailwind JSX, SVG, or raw JSON from designs
- **Pencil CLI** — check CLI status, run commands, generate batch configs

## Installation

```bash
composer require coquibot/coqui-toolkit-pencil-dev
```

The toolkit is auto-discovered by Coqui via `extra.php-agents.toolkits` in `composer.json`. No additional configuration is needed.

## Available Tools

| Tool | Description |
|------|-------------|
| `pencil_document` | Create, read, list, validate, and inspect `.pen` files |
| `pencil_design` | Insert, get, update, move, copy, and delete design elements |
| `pencil_component` | Create reusable components and instantiate them as `ref` elements |
| `pencil_variable` | Manage design variables (tokens), themes, and CSS import/export |
| `pencil_export` | Export designs to HTML/CSS, React/Tailwind, SVG, or JSON |
| `pencil_cli` | Check Pencil CLI status, run commands, generate batch configs |

## Usage Examples

### Create a Design

```
pencil_document(action: "create", path: "landing-page.pen", width: 1440, height: 900)
pencil_design(action: "insert", path: "landing-page.pen", type: "text", parent_id: "<frame_id>", properties: '{"content": "Hello World", "fontSize": 48, "fontWeight": 700}')
```

### Build a Component Library

```
# Mark an element as a reusable component
pencil_component(action: "create", path: "design.pen", component_id: "<button_id>", name: "Primary Button")

# Create an instance with overrides
pencil_component(action: "instantiate", path: "design.pen", component_id: "<button_id>", descendants: '{"label_id": {"content": "Submit"}}')
```

### Import CSS Variables

```
pencil_variable(action: "import_css", path: "design.pen", css: ":root { --primary: #3B82F6; --font-size-base: 16px; }")
```

### Export to React

```
pencil_export(action: "react", path: "design.pen", component_name: "HeroSection", output_path: "HeroSection.jsx")
```

### Export to HTML

```
pencil_export(action: "html", path: "design.pen", output_path: "preview.html")
```

### Export to SVG

```
pencil_export(action: "svg", path: "design.pen", output_path: "icon.svg")
```

## The .pen Format

A `.pen` file is a JSON document with this structure:

```json
{
    "version": "1",
    "children": [
        {
            "id": "frame-1",
            "type": "frame",
            "width": 1440,
            "height": 900,
            "layout": "vertical",
            "children": [...]
        }
    ],
    "variables": {
        "primary": { "type": "color", "value": "#3B82F6" }
    },
    "themes": {
        "mode": { "Light": true, "Dark": false }
    }
}
```

### Element Types

`rectangle`, `ellipse`, `line`, `polygon`, `path`, `text`, `frame`, `group`, `ref`, `icon_font`, `note`, `prompt`, `context`

### Containers

`frame` and `group` can hold children. Frames support flexbox layout with `layout`, `justifyContent`, `alignItems`, `gap`, and `padding`.

### Components

Any element with `reusable: true` is a component. `ref` elements are instances that reference a component and can override properties via `descendants`.

### Variables

Design tokens stored in `variables`. Referenced in element properties with `$variableName` syntax. Support themed values.

## MCP Live Preview

When Pencil desktop is running with its MCP server, use Coqui's `mcp_client` toolkit to access live preview and screenshot features:

- `batch_design` — generate designs from prompts
- `get_screenshot` — capture design screenshots
- `get_variables` / `set_variables` — live variable management

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test

# Static analysis
composer analyse
```

## License

MIT
