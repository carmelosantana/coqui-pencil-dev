<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiToolkitPencilDev\Export;

/**
 * Export .pen elements to HTML + inline CSS.
 */
final class HtmlExporter
{
    /**
     * @param list<array<string, mixed>> $elements
     * @param array<string, mixed> $variables
     */
    public function export(array $elements, array $variables = []): string
    {
        $body = '';
        foreach ($elements as $element) {
            $body .= $this->renderElement($element, $variables, 0);
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        </style>
        </head>
        <body>
        {$body}
        </body>
        </html>
        HTML;
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderElement(array $element, array $variables, int $depth): string
    {
        $type = $element['type'] ?? 'unknown';
        $indent = str_repeat('  ', $depth);

        return match ($type) {
            'frame' => $this->renderFrame($element, $variables, $indent, $depth),
            'rectangle' => $this->renderRectangle($element, $variables, $indent),
            'ellipse' => $this->renderEllipse($element, $variables, $indent),
            'text' => $this->renderText($element, $variables, $indent),
            'group' => $this->renderGroup($element, $variables, $indent, $depth),
            'line' => $this->renderLine($element, $variables, $indent),
            'icon_font' => $this->renderIcon($element, $indent),
            default => $indent . sprintf('<!-- unsupported: %s -->', htmlspecialchars($type, ENT_QUOTES)) . "\n",
        };
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderFrame(array $element, array $variables, string $indent, int $depth): string
    {
        $style = $this->buildBoxStyle($element, $variables);
        $style .= $this->buildLayoutStyle($element);
        $name = htmlspecialchars((string) ($element['name'] ?? ''), ENT_QUOTES);

        $html = $indent . sprintf('<div style="%s"', trim($style));
        if ($name !== '') {
            $html .= sprintf(' data-name="%s"', $name);
        }
        $html .= ">\n";

        foreach ($element['children'] ?? [] as $child) {
            $html .= $this->renderElement($child, $variables, $depth + 1);
        }

        $html .= $indent . "</div>\n";
        return $html;
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderRectangle(array $element, array $variables, string $indent): string
    {
        $style = $this->buildBoxStyle($element, $variables);
        return $indent . sprintf('<div style="%s"></div>', trim($style)) . "\n";
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderEllipse(array $element, array $variables, string $indent): string
    {
        $style = $this->buildBoxStyle($element, $variables);
        $style .= ' border-radius: 50%;';
        return $indent . sprintf('<div style="%s"></div>', trim($style)) . "\n";
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderText(array $element, array $variables, string $indent): string
    {
        $style = $this->buildTextStyle($element, $variables);
        $content = htmlspecialchars((string) ($element['content'] ?? ''), ENT_QUOTES);
        return $indent . sprintf('<p style="%s">%s</p>', trim($style), $content) . "\n";
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderGroup(array $element, array $variables, string $indent, int $depth): string
    {
        $html = $indent . "<div>\n";
        foreach ($element['children'] ?? [] as $child) {
            $html .= $this->renderElement($child, $variables, $depth + 1);
        }
        $html .= $indent . "</div>\n";
        return $html;
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderLine(array $element, array $variables, string $indent): string
    {
        $width = $element['width'] ?? 100;
        $color = $this->resolveColor($element['strokes'][0]['color'] ?? '#000000', $variables);
        $strokeWidth = $element['strokeWidth'] ?? 1;

        return $indent . sprintf(
            '<hr style="width: %dpx; border: none; border-top: %dpx solid %s;">',
            $width,
            $strokeWidth,
            htmlspecialchars($color, ENT_QUOTES),
        ) . "\n";
    }

    /**
     * @param array<string, mixed> $element
     */
    private function renderIcon(array $element, string $indent): string
    {
        $icon = htmlspecialchars((string) ($element['icon'] ?? 'circle'), ENT_QUOTES);
        $family = htmlspecialchars((string) ($element['iconFontFamily'] ?? 'lucide'), ENT_QUOTES);
        return $indent . sprintf('<span data-icon="%s" data-family="%s">%s</span>', $icon, $family, $icon) . "\n";
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function buildBoxStyle(array $element, array $variables): string
    {
        $parts = [];

        if (isset($element['width'])) {
            $parts[] = sprintf('width: %dpx', $element['width']);
        }
        if (isset($element['height'])) {
            $parts[] = sprintf('height: %dpx', $element['height']);
        }

        // Background from fills.
        $fills = $element['fills'] ?? [];
        if ($fills !== []) {
            $fill = $fills[0];
            $fillType = $fill['type'] ?? 'color';
            if ($fillType === 'color' && isset($fill['color'])) {
                $color = $this->resolveColor($fill['color'], $variables);
                $parts[] = sprintf('background-color: %s', $color);
            }
        }

        // Border from strokes.
        $strokes = $element['strokes'] ?? [];
        if ($strokes !== []) {
            $stroke = $strokes[0];
            $color = $this->resolveColor($stroke['color'] ?? '#000', $variables);
            $width = $stroke['width'] ?? 1;
            $parts[] = sprintf('border: %dpx solid %s', $width, $color);
        }

        // Border radius.
        if (isset($element['borderRadius'])) {
            $br = $element['borderRadius'];
            if (is_array($br)) {
                $parts[] = sprintf('border-radius: %dpx %dpx %dpx %dpx', $br[0] ?? 0, $br[1] ?? 0, $br[2] ?? 0, $br[3] ?? 0);
            } else {
                $parts[] = sprintf('border-radius: %dpx', $br);
            }
        }

        // Opacity.
        if (isset($element['opacity']) && $element['opacity'] < 1) {
            $parts[] = sprintf('opacity: %s', $element['opacity']);
        }

        return implode('; ', $parts) . ($parts !== [] ? ';' : '');
    }

    /**
     * @param array<string, mixed> $element
     */
    private function buildLayoutStyle(array $element): string
    {
        $layout = $element['layout'] ?? 'none';
        if ($layout === 'none') {
            return '';
        }

        $parts = [' display: flex'];
        $parts[] = sprintf('flex-direction: %s', $layout === 'horizontal' ? 'row' : 'column');

        $justify = $element['justifyContent'] ?? '';
        if ($justify !== '') {
            $cssJustify = str_replace('_', '-', $justify);
            $parts[] = sprintf('justify-content: %s', $cssJustify);
        }

        $align = $element['alignItems'] ?? '';
        if ($align !== '') {
            $parts[] = sprintf('align-items: %s', $align);
        }

        $gap = $element['gap'] ?? null;
        if ($gap !== null) {
            $parts[] = sprintf('gap: %dpx', $gap);
        }

        $padding = $element['padding'] ?? null;
        if ($padding !== null) {
            if (is_array($padding)) {
                $parts[] = sprintf('padding: %dpx %dpx %dpx %dpx', $padding[0] ?? 0, $padding[1] ?? 0, $padding[2] ?? 0, $padding[3] ?? 0);
            } else {
                $parts[] = sprintf('padding: %dpx', $padding);
            }
        }

        return '; ' . implode('; ', $parts) . ';';
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function buildTextStyle(array $element, array $variables): string
    {
        $parts = [];

        if (isset($element['fontSize'])) {
            $parts[] = sprintf('font-size: %dpx', $element['fontSize']);
        }
        if (isset($element['fontFamily'])) {
            $parts[] = sprintf('font-family: %s', htmlspecialchars((string) $element['fontFamily'], ENT_QUOTES));
        }
        if (isset($element['fontWeight'])) {
            $parts[] = sprintf('font-weight: %s', $element['fontWeight']);
        }
        if (isset($element['textAlign'])) {
            $parts[] = sprintf('text-align: %s', $element['textAlign']);
        }
        if (isset($element['lineHeight'])) {
            $parts[] = sprintf('line-height: %s', $element['lineHeight']);
        }

        // Text color from fills.
        $fills = $element['fills'] ?? [];
        if ($fills !== []) {
            $fill = $fills[0];
            if (($fill['type'] ?? '') === 'color' && isset($fill['color'])) {
                $color = $this->resolveColor($fill['color'], $variables);
                $parts[] = sprintf('color: %s', $color);
            }
        }

        return implode('; ', $parts) . ($parts !== [] ? ';' : '');
    }

    /**
     * Resolve a color value, substituting variables.
     *
     * @param array<string, mixed> $variables
     */
    private function resolveColor(string $color, array $variables): string
    {
        if (!str_starts_with($color, '$')) {
            return $color;
        }

        $varName = ltrim($color, '$');
        $var = $variables[$varName] ?? null;

        if ($var === null) {
            return $color;
        }

        if (is_array($var)) {
            return (string) ($var['value'] ?? $color);
        }

        return (string) $var;
    }
}
