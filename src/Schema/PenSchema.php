<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiToolkitPencilDev\Schema;

/**
 * Element type definitions and validation rules derived from Pencil's TypeScript schema.
 *
 * @see https://docs.pencil.dev/for-developers/the-pen-format
 */
final class PenSchema
{
    /** All known element types in the .pen format. */
    public const ELEMENT_TYPES = [
        'rectangle',
        'ellipse',
        'line',
        'polygon',
        'path',
        'text',
        'frame',
        'group',
        'ref',
        'icon_font',
        'note',
        'prompt',
        'context',
    ];

    /** Element types that can contain children. */
    public const CONTAINER_TYPES = ['frame', 'group'];

    /** Element types that support fill/stroke/effect graphics. */
    public const GRAPHIC_TYPES = [
        'rectangle',
        'ellipse',
        'line',
        'polygon',
        'path',
        'text',
        'frame',
    ];

    /** Valid layout directions. */
    public const LAYOUT_DIRECTIONS = ['none', 'vertical', 'horizontal'];

    /** Valid justify-content values. */
    public const JUSTIFY_CONTENT = ['start', 'center', 'end', 'space_between', 'space_around'];

    /** Valid align-items values. */
    public const ALIGN_ITEMS = ['start', 'center', 'end'];

    /** Valid text alignment values. */
    public const TEXT_ALIGN = ['left', 'center', 'right', 'justify'];

    /** Valid text vertical alignment values. */
    public const TEXT_ALIGN_VERTICAL = ['top', 'middle', 'bottom'];

    /** Valid text growth modes. */
    public const TEXT_GROWTH = ['auto', 'fixed-width', 'fixed-width-height'];

    /** Valid stroke alignment values. */
    public const STROKE_ALIGN = ['inside', 'center', 'outside'];

    /** Valid fill types. */
    public const FILL_TYPES = ['color', 'gradient', 'image', 'mesh_gradient'];

    /** Valid gradient types. */
    public const GRADIENT_TYPES = ['linear', 'radial', 'angular'];

    /** Valid image fill modes. */
    public const IMAGE_MODES = ['stretch', 'fill', 'fit'];

    /** Valid blend modes. */
    public const BLEND_MODES = [
        'normal', 'darken', 'multiply', 'linearBurn', 'colorBurn',
        'light', 'screen', 'linearDodge', 'colorDodge', 'overlay',
        'softLight', 'hardLight', 'difference', 'exclusion',
        'hue', 'saturation', 'color', 'luminosity',
    ];

    /** Valid effect types. */
    public const EFFECT_TYPES = ['blur', 'background_blur', 'shadow'];

    /** Valid shadow types. */
    public const SHADOW_TYPES = ['inner', 'outer'];

    /** Variable types supported by Pencil. */
    public const VARIABLE_TYPES = ['color', 'number', 'string', 'boolean'];

    /** Built-in icon font families. */
    public const ICON_FONTS = [
        'lucide',
        'feather',
        'Material Symbols Outlined',
        'Material Symbols Rounded',
        'Material Symbols Sharp',
        'phosphor',
    ];

    /**
     * Required fields per element type.
     *
     * @return array<string, list<string>>
     */
    public static function requiredFields(): array
    {
        return [
            'rectangle' => ['id', 'type'],
            'ellipse' => ['id', 'type'],
            'line' => ['id', 'type'],
            'polygon' => ['id', 'type'],
            'path' => ['id', 'type'],
            'text' => ['id', 'type'],
            'frame' => ['id', 'type'],
            'group' => ['id', 'type'],
            'ref' => ['id', 'type', 'ref'],
            'icon_font' => ['id', 'type'],
            'note' => ['id', 'type'],
            'prompt' => ['id', 'type'],
            'context' => ['id', 'type'],
        ];
    }

    /**
     * Validate an element against schema rules.
     *
     * @param array<string, mixed> $element
     * @return list<string> Validation errors (empty if valid).
     */
    public static function validateElement(array $element): array
    {
        $errors = [];

        $type = $element['type'] ?? null;
        if ($type === null) {
            $errors[] = 'Element is missing required "type" field.';
            return $errors;
        }

        if (!in_array($type, self::ELEMENT_TYPES, true)) {
            $errors[] = sprintf('Unknown element type "%s". Valid types: %s', $type, implode(', ', self::ELEMENT_TYPES));
            return $errors;
        }

        if (!isset($element['id']) || !is_string($element['id']) || $element['id'] === '') {
            $errors[] = 'Element is missing required "id" field.';
        } elseif (str_contains($element['id'], '/')) {
            $errors[] = 'Element "id" must not contain slash (/) characters.';
        }

        $required = self::requiredFields()[$type] ?? [];
        foreach ($required as $field) {
            if ($field === 'id' || $field === 'type') {
                continue; // Already checked above.
            }
            if (!isset($element[$field]) || (is_string($element[$field]) && trim($element[$field]) === '')) {
                $errors[] = sprintf('Element type "%s" requires field "%s".', $type, $field);
            }
        }

        if ($type === 'text' && isset($element['textGrowth'])) {
            if (!in_array($element['textGrowth'], self::TEXT_GROWTH, true)) {
                $errors[] = sprintf('Invalid textGrowth "%s". Valid: %s', $element['textGrowth'], implode(', ', self::TEXT_GROWTH));
            }
        }

        if (isset($element['layout']) && !in_array($element['layout'], self::LAYOUT_DIRECTIONS, true)) {
            $errors[] = sprintf('Invalid layout "%s". Valid: %s', $element['layout'], implode(', ', self::LAYOUT_DIRECTIONS));
        }

        if (isset($element['children']) && !in_array($type, self::CONTAINER_TYPES, true)) {
            $errors[] = sprintf('Element type "%s" cannot have children. Only frame and group can.', $type);
        }

        return $errors;
    }

    /**
     * Check if a string value is a variable reference (starts with $).
     */
    public static function isVariableReference(string $value): bool
    {
        return str_starts_with($value, '$');
    }

    /**
     * Extract the variable name from a reference string.
     */
    public static function extractVariableName(string $reference): string
    {
        return ltrim($reference, '$');
    }

    /**
     * Check if a color string is valid (hex format).
     */
    public static function isValidColor(string $color): bool
    {
        if (self::isVariableReference($color)) {
            return true;
        }

        return (bool) preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$/', $color);
    }

    /**
     * Build a default element structure for a given type.
     *
     * @return array<string, mixed>
     */
    public static function defaultElement(string $type, string $id): array
    {
        $base = [
            'id' => $id,
            'type' => $type,
            'x' => 0,
            'y' => 0,
        ];

        return match ($type) {
            'rectangle' => array_merge($base, ['width' => 100, 'height' => 100]),
            'ellipse' => array_merge($base, ['width' => 100, 'height' => 100]),
            'line' => array_merge($base, ['width' => 200, 'height' => 0]),
            'polygon' => array_merge($base, ['width' => 100, 'height' => 100, 'polygonCount' => 6]),
            'path' => array_merge($base, ['width' => 100, 'height' => 100, 'geometry' => '']),
            'text' => array_merge($base, ['content' => 'Text']),
            'frame' => array_merge($base, ['width' => 400, 'height' => 300, 'children' => []]),
            'group' => array_merge($base, ['children' => []]),
            'ref' => ['id' => $id, 'type' => 'ref', 'x' => 0, 'y' => 0, 'ref' => ''],
            'icon_font' => array_merge($base, ['iconFontFamily' => 'lucide', 'icon' => 'circle']),
            'note' => array_merge($base, ['width' => 200, 'height' => 200, 'content' => '']),
            'prompt' => array_merge($base, ['width' => 300, 'height' => 100, 'content' => '']),
            'context' => array_merge($base, ['width' => 300, 'height' => 100, 'content' => '']),
            default => $base,
        };
    }
}
