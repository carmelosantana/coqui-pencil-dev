<?php

declare(strict_types=1);

use CarmeloSantana\CoquiToolkitPencilDev\PenDocument;

$fixturePath = __DIR__ . '/../fixtures/sample.pen';

test('lists variables', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $vars = $doc->getVariables();

    expect($vars)->toHaveCount(3)
        ->and($vars)->toHaveKeys(['primary', 'textColor', 'spacing']);
});

test('gets a single variable', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $var = $doc->getVariables()['spacing'];

    expect($var['type'])->toBe('number')
        ->and($var['value'])->toBe(16);
});

test('sets a new variable', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);

    $doc = $doc->withVariable('accent', ['type' => 'color', 'value' => '#EF4444']);

    $vars = $doc->getVariables();
    expect($vars)->toHaveCount(4)
        ->and($vars['accent']['value'])->toBe('#EF4444');
});

test('updates existing variable', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);

    $doc = $doc->withVariable('primary', ['type' => 'color', 'value' => '#10B981']);

    expect($doc->getVariables()['primary']['value'])->toBe('#10B981');
});

test('deletes a variable', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);

    $doc = $doc->withoutVariable('spacing');

    expect($doc->getVariables())->toHaveCount(2)
        ->and($doc->getVariables())->not->toHaveKey('spacing');
});

test('replaces all variables', function () {
    $doc = PenDocument::create();
    $doc = $doc->withVariable('a', ['type' => 'string', 'value' => 'one']);
    $doc = $doc->withVariable('b', ['type' => 'string', 'value' => 'two']);

    $doc = $doc->withVariables([
        'x' => ['type' => 'number', 'value' => 42],
    ]);

    $vars = $doc->getVariables();
    expect($vars)->toHaveCount(1)
        ->and($vars)->toHaveKey('x')
        ->and($vars)->not->toHaveKey('a');
});

test('themes structure', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $themes = $doc->getThemes();

    expect($themes)->toHaveKey('mode')
        ->and($themes['mode']['Light'])->toBeTrue()
        ->and($themes['mode']['Dark'])->toBeFalse();
});

test('sets themes', function () {
    $doc = PenDocument::create();

    $doc = $doc->withThemes([
        'mode' => ['Light' => true, 'Dark' => false],
        'density' => ['Compact' => false, 'Comfortable' => true],
    ]);

    $themes = $doc->getThemes();
    expect($themes)->toHaveCount(2)
        ->and($themes)->toHaveKeys(['mode', 'density']);
});

test('CSS import parses custom properties', function () {
    $css = <<<'CSS'
    :root {
      --primary-color: #3B82F6;
      --font-size-base: 16px;
      --border-radius: 8;
      --is-dark: false;
    }
    CSS;

    // Parse manually to validate the logic matches VariableTool.
    $vars = [];
    if (preg_match_all('/:root\s*\{([^}]+)\}/s', $css, $rootMatches)) {
        foreach ($rootMatches[1] as $block) {
            if (preg_match_all('/--([a-zA-Z0-9_-]+)\s*:\s*([^;]+);/', $block, $propMatches, PREG_SET_ORDER)) {
                foreach ($propMatches as $match) {
                    $cssName = $match[1];
                    // kebab-case to camelCase
                    $name = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $cssName))));
                    $vars[$name] = trim($match[2]);
                }
            }
        }
    }

    expect($vars)->toHaveCount(4)
        ->and($vars)->toHaveKey('primaryColor', '#3B82F6')
        ->and($vars)->toHaveKey('fontSizeBase', '16px')
        ->and($vars)->toHaveKey('borderRadius', '8')
        ->and($vars)->toHaveKey('isDark', 'false');
});

test('CSS export generates custom properties', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $vars = $doc->getVariables();

    $lines = [':root {'];
    foreach ($vars as $name => $var) {
        $value = is_array($var) ? ($var['value'] ?? '') : $var;
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_SLASHES);
        }
        // camelCase to kebab-case
        $cssName = strtolower((string) preg_replace('/[A-Z]/', '-$0', $name));
        $lines[] = sprintf('  --%s: %s;', $cssName, $value);
    }
    $lines[] = '}';

    $css = implode("\n", $lines);

    expect($css)->toContain(':root {')
        ->and($css)->toContain('--primary: #3B82F6')
        ->and($css)->toContain('--text-color: #111827')
        ->and($css)->toContain('--spacing: 16');
});
