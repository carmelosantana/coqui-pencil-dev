<?php

declare(strict_types=1);

use CarmeloSantana\CoquiToolkitPencilDev\PenDocument;

$fixturePath = __DIR__ . '/../fixtures/sample.pen';

test('creates a new empty document', function () {
    $doc = PenDocument::create(1920, 1080);

    $data = $doc->toArray();
    expect($data)->toHaveKey('version', '1')
        ->and($data['children'])->toHaveCount(1)
        ->and($data['children'][0]['type'])->toBe('frame')
        ->and($data['children'][0]['width'])->toBe(1920)
        ->and($data['children'][0]['height'])->toBe(1080);
});

test('reads a .pen file', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);

    expect($doc->toArray())->toHaveKey('version', '1')
        ->and($doc->getChildren())->toHaveCount(1)
        ->and($doc->getChildren()[0]['id'])->toBe('frame-1');
});

test('throws on missing file', function () {
    PenDocument::fromFile('/nonexistent/file.pen');
})->throws(\RuntimeException::class, 'File not found');

test('validates a well-formed document', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $errors = $doc->validate();

    expect($errors)->toBeEmpty();
});

test('finds element by ID', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);

    $element = $doc->findById('hero-rect');
    expect($element)->not->toBeNull()
        ->and($element['type'])->toBe('rectangle')
        ->and($element['name'])->toBe('Hero Card');
});

test('finds elements by type', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);

    $texts = $doc->findByType('text');
    expect($texts)->toHaveCount(1)
        ->and($texts[0]['content'])->toBe('Welcome to Pencil');
});

test('returns document info', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $info = $doc->info();

    expect($info['elements'])->toBe(4)
        ->and($info['variables'])->toBe(3)
        ->and($info['themes'])->toBe(1)
        ->and($info['elements_by_type'])->toHaveKey('frame')
        ->and($info['elements_by_type'])->toHaveKey('text')
        ->and($info['elements_by_type'])->toHaveKey('rectangle')
        ->and($info['elements_by_type'])->toHaveKey('ellipse');
});

test('extracts variables', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $vars = $doc->getVariables();

    expect($vars)->toHaveKey('primary')
        ->and($vars['primary']['type'])->toBe('color')
        ->and($vars['primary']['value'])->toBe('#3B82F6');
});

test('extracts themes', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $themes = $doc->getThemes();

    expect($themes)->toHaveKey('mode')
        ->and($themes['mode'])->toHaveKey('Light')
        ->and($themes['mode'])->toHaveKey('Dark');
});

test('adds element to root', function () {
    $doc = PenDocument::create();
    $frameId = $doc->getChildren()[0]['id'];

    $doc = $doc->withElement([
        'id' => 'new-rect',
        'type' => 'rectangle',
        'x' => 0,
        'y' => 0,
        'width' => 100,
        'height' => 100,
    ]);

    expect($doc->getChildren())->toHaveCount(2)
        ->and($doc->findById('new-rect'))->not->toBeNull();
});

test('adds element to a parent', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);

    $doc = $doc->withElement([
        'id' => 'child-rect',
        'type' => 'rectangle',
        'x' => 0,
        'y' => 0,
        'width' => 50,
        'height' => 50,
    ], 'frame-1');

    $frame = $doc->findById('frame-1');
    expect($frame['children'])->toHaveCount(4);
});

test('updates element properties', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);

    $doc = $doc->withUpdatedElement('hero-rect', ['width' => 800]);

    $element = $doc->findById('hero-rect');
    expect($element['width'])->toBe(800);
});

test('removes element', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);

    $doc = $doc->withoutElement('circle-1');

    expect($doc->findById('circle-1'))->toBeNull();
    $frame = $doc->findById('frame-1');
    expect($frame['children'])->toHaveCount(2);
});

test('copies element with new IDs', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);

    $result = $doc->copyElement('hero-rect');
    expect($result)->not->toBeNull()
        ->and($result['element']['id'])->not->toBe('hero-rect')
        ->and($result['element']['type'])->toBe('rectangle');
});

test('sets and removes variables', function () {
    $doc = PenDocument::create();

    $doc = $doc->withVariable('accent', ['type' => 'color', 'value' => '#EF4444']);
    expect($doc->getVariables())->toHaveKey('accent');

    $doc = $doc->withoutVariable('accent');
    expect($doc->getVariables())->not->toHaveKey('accent');
});

test('serializes to JSON and back', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $json = $doc->toJson();
    $parsed = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

    expect($parsed['version'])->toBe('1')
        ->and($parsed['children'])->toHaveCount(1);
});

test('saves and reads roundtrip', function () {
    $tmpFile = sys_get_temp_dir() . '/pencil_test_' . uniqid() . '.pen';

    try {
        $doc = PenDocument::create(800, 600);
        $doc = $doc->withVariable('bg', ['type' => 'color', 'value' => '#FAFAFA']);
        $doc->save($tmpFile);

        $loaded = PenDocument::fromFile($tmpFile);
        expect($loaded->getVariables())->toHaveKey('bg')
            ->and($loaded->getChildren()[0]['width'])->toBe(800);
    } finally {
        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
    }
});
