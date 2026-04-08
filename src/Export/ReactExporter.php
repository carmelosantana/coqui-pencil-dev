<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiToolkitPencilDev\Export;

/**
 * Export .pen elements to React/JSX components with Tailwind CSS classes.
 */
final class ReactExporter
{
    /**
     * @param list<array<string, mixed>> $elements
     * @param array<string, mixed> $variables
     */
    public function export(array $elements, array $variables = [], string $componentName = 'Design'): string
    {
        $body = '';
        foreach ($elements as $element) {
            $body .= $this->renderElement($element, $variables, 2);
        }

        return <<<JSX
        import React from 'react';

        export default function {$componentName}() {
          return (
            <>
        {$body}    </>
          );
        }
        JSX;
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
            default => $indent . sprintf('{/* unsupported: %s */}', $type) . "\n",
        };
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderFrame(array $element, array $variables, string $indent, int $depth): string
    {
        $classes = $this->buildBoxClasses($element, $variables);
        $classes .= $this->buildLayoutClasses($element);

        $jsx = $indent . sprintf('<div className="%s">', trim($classes)) . "\n";

        foreach ($element['children'] ?? [] as $child) {
            $jsx .= $this->renderElement($child, $variables, $depth + 1);
        }

        $jsx .= $indent . "</div>\n";
        return $jsx;
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderRectangle(array $element, array $variables, string $indent): string
    {
        $classes = $this->buildBoxClasses($element, $variables);
        return $indent . sprintf('<div className="%s" />', trim($classes)) . "\n";
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderEllipse(array $element, array $variables, string $indent): string
    {
        $classes = $this->buildBoxClasses($element, $variables) . ' rounded-full';
        return $indent . sprintf('<div className="%s" />', trim($classes)) . "\n";
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderText(array $element, array $variables, string $indent): string
    {
        $classes = $this->buildTextClasses($element, $variables);
        $content = htmlspecialchars((string) ($element['content'] ?? ''), ENT_QUOTES);
        return $indent . sprintf('<p className="%s">%s</p>', trim($classes), $content) . "\n";
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function renderGroup(array $element, array $variables, string $indent, int $depth): string
    {
        $jsx = $indent . "<div>\n";
        foreach ($element['children'] ?? [] as $child) {
            $jsx .= $this->renderElement($child, $variables, $depth + 1);
        }
        $jsx .= $indent . "</div>\n";
        return $jsx;
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function buildBoxClasses(array $element, array $variables): string
    {
        $classes = [];

        // Dimensions — use Tailwind arbitrary values.
        if (isset($element['width'])) {
            $classes[] = sprintf('w-[%dpx]', $element['width']);
        }
        if (isset($element['height'])) {
            $classes[] = sprintf('h-[%dpx]', $element['height']);
        }

        // Background color.
        $fills = $element['fills'] ?? [];
        if ($fills !== []) {
            $fill = $fills[0];
            if (($fill['type'] ?? '') === 'color' && isset($fill['color'])) {
                $color = $this->resolveColor($fill['color'], $variables);
                $classes[] = sprintf('bg-[%s]', $color);
            }
        }

        // Border.
        $strokes = $element['strokes'] ?? [];
        if ($strokes !== []) {
            $stroke = $strokes[0];
            $color = $this->resolveColor($stroke['color'] ?? '#000', $variables);
            $width = $stroke['width'] ?? 1;
            $classes[] = sprintf('border-[%dpx]', $width);
            $classes[] = sprintf('border-[%s]', $color);
        }

        // Border radius.
        if (isset($element['borderRadius'])) {
            $br = $element['borderRadius'];
            if (is_array($br)) {
                $classes[] = sprintf('rounded-[%dpx_%dpx_%dpx_%dpx]', $br[0] ?? 0, $br[1] ?? 0, $br[2] ?? 0, $br[3] ?? 0);
            } else {
                $classes[] = sprintf('rounded-[%dpx]', $br);
            }
        }

        // Opacity.
        if (isset($element['opacity']) && $element['opacity'] < 1) {
            $pct = (int) ($element['opacity'] * 100);
            $classes[] = sprintf('opacity-[%d%%]', $pct);
        }

        return implode(' ', $classes);
    }

    /**
     * @param array<string, mixed> $element
     */
    private function buildLayoutClasses(array $element): string
    {
        $layout = $element['layout'] ?? 'none';
        if ($layout === 'none') {
            return '';
        }

        $classes = [' flex'];
        $classes[] = $layout === 'horizontal' ? 'flex-row' : 'flex-col';

        $justify = $element['justifyContent'] ?? '';
        $justifyMap = [
            'start' => 'justify-start',
            'center' => 'justify-center',
            'end' => 'justify-end',
            'space_between' => 'justify-between',
            'space_around' => 'justify-around',
        ];
        if (isset($justifyMap[$justify])) {
            $classes[] = $justifyMap[$justify];
        }

        $align = $element['alignItems'] ?? '';
        $alignMap = [
            'start' => 'items-start',
            'center' => 'items-center',
            'end' => 'items-end',
        ];
        if (isset($alignMap[$align])) {
            $classes[] = $alignMap[$align];
        }

        $gap = $element['gap'] ?? null;
        if ($gap !== null) {
            $classes[] = sprintf('gap-[%dpx]', $gap);
        }

        $padding = $element['padding'] ?? null;
        if ($padding !== null) {
            if (is_array($padding)) {
                $classes[] = sprintf('p-[%dpx_%dpx_%dpx_%dpx]', $padding[0] ?? 0, $padding[1] ?? 0, $padding[2] ?? 0, $padding[3] ?? 0);
            } else {
                $classes[] = sprintf('p-[%dpx]', $padding);
            }
        }

        return ' ' . implode(' ', $classes);
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, mixed> $variables
     */
    private function buildTextClasses(array $element, array $variables): string
    {
        $classes = [];

        if (isset($element['fontSize'])) {
            $classes[] = sprintf('text-[%dpx]', $element['fontSize']);
        }
        if (isset($element['fontWeight'])) {
            $weight = $element['fontWeight'];
            $weightMap = [
                100 => 'font-thin', 200 => 'font-extralight', 300 => 'font-light',
                400 => 'font-normal', 500 => 'font-medium', 600 => 'font-semibold',
                700 => 'font-bold', 800 => 'font-extrabold', 900 => 'font-black',
            ];
            $classes[] = $weightMap[$weight] ?? sprintf('font-[%s]', $weight);
        }
        if (isset($element['textAlign'])) {
            $classes[] = sprintf('text-%s', $element['textAlign']);
        }

        // Text color from fills.
        $fills = $element['fills'] ?? [];
        if ($fills !== []) {
            $fill = $fills[0];
            if (($fill['type'] ?? '') === 'color' && isset($fill['color'])) {
                $color = $this->resolveColor($fill['color'], $variables);
                $classes[] = sprintf('text-[%s]', $color);
            }
        }

        return implode(' ', $classes);
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
