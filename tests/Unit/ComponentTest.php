<?php

declare(strict_types=1);

use CarmeloSantana\CoquiToolkitPencilDev\PenDocument;

$fixturePath = __DIR__ . '/../fixtures/components.pen';

test('gets reusable components', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $components = $doc->getComponents();

    expect($components)->toHaveCount(1)
        ->and($components[0]['id'])->toBe('btn-component')
        ->and($components[0]['reusable'])->toBeTrue()
        ->and($components[0]['name'])->toBe('Button');
});

test('gets ref instances', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $instances = $doc->getInstances();

    expect($instances)->toHaveCount(2)
        ->and($instances[0]['type'])->toBe('ref')
        ->and($instances[0]['ref'])->toBe('btn-component')
        ->and($instances[1]['ref'])->toBe('btn-component');
});

test('instance has descendant overrides', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);

    $instance = $doc->findById('btn-instance-1');
    expect($instance)->not->toBeNull()
        ->and($instance['descendants'])->toHaveKey('btn-label')
        ->and($instance['descendants']['btn-label']['content'])->toBe('Submit');
});

test('marks element as reusable component', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);

    // Mark an existing non-reusable element — use the btn-label text element.
    $doc = $doc->withUpdatedElement('btn-label', ['reusable' => true]);

    $components = $doc->getComponents();
    expect($components)->toHaveCount(2);
});

test('creates ref instance with overrides', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);

    $refId = PenDocument::generateId();
    $ref = [
        'id' => $refId,
        'type' => 'ref',
        'ref' => 'btn-component',
        'x' => 400,
        'y' => 200,
        'descendants' => [
            'btn-label' => ['content' => 'Cancel'],
        ],
    ];

    $doc = $doc->withElement($ref);

    $instances = $doc->getInstances();
    expect($instances)->toHaveCount(3);

    $newInst = $doc->findById($refId);
    expect($newInst)->not->toBeNull()
        ->and($newInst['descendants']['btn-label']['content'])->toBe('Cancel');
});

test('updates instance descendants', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);

    $doc = $doc->withUpdatedElement('btn-instance-1', [
        'descendants' => [
            'btn-label' => ['content' => 'Save'],
        ],
    ]);

    $updated = $doc->findById('btn-instance-1');
    expect($updated['descendants']['btn-label']['content'])->toBe('Save');
});

test('component info counts instances', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $info = $doc->info();

    expect($info['components'])->toBe(1)
        ->and($info['instances'])->toBe(2);
});
