<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiToolkitPencilDev\Export;

/**
 * Export .pen vector elements to SVG.
 */
final class SvgExporter
{
    /**
     * @param list<array<string, mixed>> $elements
     * @param array<string, mixed> $variables
     */
    public function export(array $elements, array $variables = [], ?int $viewBoxWidth = null, ?int $viewBoxHeight = null): string
    {
        // Auto-detect viewBox from first frame if not specified.
        if ($viewBoxWidth === null || $viewBoxHeight === null) {
            foreach ($elements as $el) {
                if (($el['type'] ?? '') === 'frame') {
                    $viewBoxWidth ??= (int) ($el['width'] ?? 800);
                    $viewBoxHeight ??= (int) ($el['height'] ?? 600);
                    break;
                }
            }
        }
        $viewBoxWidth ??= 800;
        $viewBoxHeight ??= 600;

        $inner = '';
        foreach ($elements as $element) {
            $inner .= $this->renderElement($element, $variables, 1);
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d">%s</svg>',
            $viewBoxWidth,
            $viewBoxHeight,
            $viewBoxWidth,
            $viewBoxHeight,
            "\n" . $inner,
        );
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
            'rectangle' => $this->renderRectangle($element, $variables, $indent),
            'ellipse' => $this->renderEllipse($element, $variables, $indent),
            'line' => $this->renderLine($element, $variables, $indent),
            'polygon' => $this->renderPolygon($element, $variables, $indent),
            'path' => $this->renderPath($element, $variables, $indent),
            'text' => $this->renderText($element, $variables, $indent),
            'frame' => $this->renderGroup($element, $variables, $indent, $depth),
            'group' => $this->renderGroup($element, $variables, $indent, $depth),
            default => $indent . sprintf('<!-- unsupported: %s -->', htmlspecialchars($type, ENT_QUOTES)) . "\n",
        };
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderRectangle(array $element, array $variables, string $indent): string
    {
        $x = $element['x'] ?? 0;
        $y = $element['y'] ?? 0;
        $w = $element['width'] ?? 100;
        $h = $element['height'] ?? 100;
        $attrs = $this->buildGraphicAttrs($element, $variables);

        $rx = '';
        if (isset($element['borderRadius'])) {
            $br = $element['borderRadius'];
            $r = is_array($br) ? ($br[0] ?? 0) : $br;
            if ($r > 0) {
                $rx = sprintf(' rx="%d"', $r);
            }
        }

        return $indent . sprintf('<rect x="%s" y="%s" width="%s" height="%s"%s%s />', $x, $y, $w, $h, $rx, $attrs) . "\n";
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderEllipse(array $element, array $variables, string $indent): string
    {
        $x = $element['x'] ?? 0;
        $y = $element['y'] ?? 0;
        $w = $element['width'] ?? 100;
        $h = $element['height'] ?? 100;
        $cx = $x + $w / 2;
        $cy = $y + $h / 2;
        $rx = $w / 2;
        $ry = $h / 2;
        $attrs = $this->buildGraphicAttrs($element, $variables);

        return $indent . sprintf('<ellipse cx="%s" cy="%s" rx="%s" ry="%s"%s />', $cx, $cy, $rx, $ry, $attrs) . "\n";
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderLine(array $element, array $variables, string $indent): string
    {
        $x = $element['x'] ?? 0;
        $y = $element['y'] ?? 0;
        $w = $element['width'] ?? 100;
        $h = $element['height'] ?? 0;
        $attrs = $this->buildGraphicAttrs($element, $variables);

        return $indent . sprintf('<line x1="%s" y1="%s" x2="%s" y2="%s"%s />', $x, $y, $x + $w, $y + $h, $attrs) . "\n";
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderPolygon(array $element, array $variables, string $indent): string
    {
        $x = $element['x'] ?? 0;
        $y = $element['y'] ?? 0;
        $w = $element['width'] ?? 100;
        $h = $element['height'] ?? 100;
        $count = $element['polygonCount'] ?? 6;
        $attrs = $this->buildGraphicAttrs($element, $variables);

        // Generate regular polygon points.
        $cx = $x + $w / 2;
        $cy = $y + $h / 2;
        $rx = $w / 2;
        $ry = $h / 2;
        $points = [];
        for ($i = 0; $i < $count; $i++) {
            $angle = (2 * M_PI * $i / $count) - (M_PI / 2);
            $px = round($cx + $rx * cos($angle), 2);
            $py = round($cy + $ry * sin($angle), 2);
            $points[] = "$px,$py";
        }

        return $indent . sprintf('<polygon points="%s"%s />', implode(' ', $points), $attrs) . "\n";
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderPath(array $element, array $variables, string $indent): string
    {
        $d = $element['geometry'] ?? '';
        $attrs = $this->buildGraphicAttrs($element, $variables);

        if ($d === '') {
            return $indent . "<!-- empty path -->\n";
        }

        return $indent . sprintf('<path d="%s"%s />', htmlspecialchars((string) $d, ENT_QUOTES), $attrs) . "\n";
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderText(array $element, array $variables, string $indent): string
    {
        $x = $element['x'] ?? 0;
        $y = $element['y'] ?? 0;
        $content = htmlspecialchars((string) ($element['content'] ?? ''), ENT_QUOTES);
        $attrs = '';

        if (isset($element['fontSize'])) {
            $attrs .= sprintf(' font-size="%d"', $element['fontSize']);
        }
        if (isset($element['fontFamily'])) {
            $attrs .= sprintf(' font-family="%s"', htmlspecialchars((string) $element['fontFamily'], ENT_QUOTES));
        }
        if (isset($element['fontWeight'])) {
            $attrs .= sprintf(' font-weight="%s"', $element['fontWeight']);
        }

        // Text color from fills.
        $fills = $element['fills'] ?? [];
        if ($fills !== []) {
            $fill = $fills[0];
            if (($fill['type'] ?? '') === 'color' && isset($fill['color'])) {
                $color = $this->resolveColor($fill['color'], $variables);
                $attrs .= sprintf(' fill="%s"', htmlspecialchars($color, ENT_QUOTES));
            }
        }

        return $indent . sprintf('<text x="%s" y="%s"%s>%s</text>', $x, $y, $attrs, $content) . "\n";
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderGroup(array $element, array $variables, string $indent, int $depth): string
    {
        $svg = $indent . "<g>\n";
        foreach ($element['children'] ?? [] as $child) {
            $svg .= $this->renderElement($child, $variables, $depth + 1);
        }
        $svg .= $indent . "</g>\n";
        return $svg;
    }

    /**
     * Build fill/stroke SVG attributes from element data.
     *
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function buildGraphicAttrs(array $element, array $variables): string
    {
        $attrs = '';

        // Fill from fills array.
        $fills = $element['fills'] ?? [];
        if ($fills !== []) {
            $fill = $fills[0];
            if (($fill['type'] ?? '') === 'color' && isset($fill['color'])) {
                $color = $this->resolveColor($fill['color'], $variables);
                $attrs .= sprintf(' fill="%s"', htmlspecialchars($color, ENT_QUOTES));
            }
        } else {
            $attrs .= ' fill="none"';
        }

        // Stroke.
        $strokes = $element['strokes'] ?? [];
        if ($strokes !== []) {
            $stroke = $strokes[0];
            $color = $this->resolveColor($stroke['color'] ?? '#000', $variables);
            $width = $stroke['width'] ?? 1;
            $attrs .= sprintf(' stroke="%s" stroke-width="%d"', htmlspecialchars($color, ENT_QUOTES), $width);
        }

        // Opacity.
        if (isset($element['opacity']) && $element['opacity'] < 1) {
            $attrs .= sprintf(' opacity="%s"', $element['opacity']);
        }

        return $attrs;
    }

    /**
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

        return is_array($var) ? (string) ($var['value'] ?? $color) : (string) $var;
    }
}
