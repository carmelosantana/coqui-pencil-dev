<?php

declare(strict_types=1);

use CarmeloSantana\CoquiToolkitPencilDev\Export\HtmlExporter;
use CarmeloSantana\CoquiToolkitPencilDev\Export\ReactExporter;
use CarmeloSantana\CoquiToolkitPencilDev\Export\SvgExporter;
use CarmeloSantana\CoquiToolkitPencilDev\PenDocument;

$fixturePath = __DIR__ . '/../fixtures/sample.pen';

// ─── HTML Export ────────────────────────────────────────────────────

test('HTML export produces valid structure', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $exporter = new HtmlExporter();
    $html = $exporter->export($doc->getChildren(), $doc->getVariables());

    expect($html)->toContain('<!DOCTYPE html>')
        ->and($html)->toContain('<body>')
        ->and($html)->toContain('</body>')
        ->and($html)->toContain('</html>');
});

test('HTML export renders text content', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $exporter = new HtmlExporter();
    $html = $exporter->export($doc->getChildren(), $doc->getVariables());

    expect($html)->toContain('Welcome to Pencil')
        ->and($html)->toContain('font-size: 48px');
});

test('HTML export resolves variable colors', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $exporter = new HtmlExporter();
    $html = $exporter->export($doc->getChildren(), $doc->getVariables());

    // $primary should resolve to #3B82F6
    expect($html)->toContain('#3B82F6')
        // $textColor should resolve to #111827
        ->and($html)->toContain('#111827');
});

test('HTML export handles frame layout', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $exporter = new HtmlExporter();
    $html = $exporter->export($doc->getChildren(), $doc->getVariables());

    expect($html)->toContain('display: flex')
        ->and($html)->toContain('flex-direction: column')
        ->and($html)->toContain('gap: 24px');
});

test('HTML export renders rectangle with border', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $exporter = new HtmlExporter();
    $html = $exporter->export($doc->getChildren(), $doc->getVariables());

    expect($html)->toContain('width: 600px')
        ->and($html)->toContain('height: 300px')
        ->and($html)->toContain('border-radius: 16px')
        ->and($html)->toContain('border: 1px solid #E5E7EB');
});

test('HTML export renders ellipse with border-radius 50%', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $exporter = new HtmlExporter();
    $html = $exporter->export($doc->getChildren(), $doc->getVariables());

    expect($html)->toContain('border-radius: 50%');
});

// ─── React/JSX Export ───────────────────────────────────────────────

test('React export produces component', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $exporter = new ReactExporter();
    $jsx = $exporter->export($doc->getChildren(), $doc->getVariables(), 'HeroSection');

    expect($jsx)->toContain("export default function HeroSection()")
        ->and($jsx)->toContain("import React from 'react'");
});

test('React export uses Tailwind arbitrary values', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $exporter = new ReactExporter();
    $jsx = $exporter->export($doc->getChildren(), $doc->getVariables());

    expect($jsx)->toContain('w-[1440px]')
        ->and($jsx)->toContain('h-[900px]')
        ->and($jsx)->toContain('w-[600px]')
        ->and($jsx)->toContain('h-[300px]');
});

test('React export resolves variable colors', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $exporter = new ReactExporter();
    $jsx = $exporter->export($doc->getChildren(), $doc->getVariables());

    expect($jsx)->toContain('bg-[#3B82F6]')
        ->and($jsx)->toContain('text-[#111827]');
});

test('React export renders layout classes', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $exporter = new ReactExporter();
    $jsx = $exporter->export($doc->getChildren(), $doc->getVariables());

    expect($jsx)->toContain('flex')
        ->and($jsx)->toContain('flex-col')
        ->and($jsx)->toContain('justify-center')
        ->and($jsx)->toContain('items-center')
        ->and($jsx)->toContain('gap-[24px]');
});

test('React export renders ellipse as rounded-full', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $exporter = new ReactExporter();
    $jsx = $exporter->export($doc->getChildren(), $doc->getVariables());

    expect($jsx)->toContain('rounded-full');
});

// ─── SVG Export ─────────────────────────────────────────────────────

test('SVG export produces valid SVG', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $exporter = new SvgExporter();
    $svg = $exporter->export($doc->getChildren(), $doc->getVariables());

    expect($svg)->toContain('<svg xmlns="http://www.w3.org/2000/svg"')
        ->and($svg)->toContain('viewBox="0 0 1440 900"')
        ->and($svg)->toContain('</svg>');
});

test('SVG export renders rectangle', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $exporter = new SvgExporter();
    $svg = $exporter->export($doc->getChildren(), $doc->getVariables());

    expect($svg)->toContain('<rect ')
        ->and($svg)->toContain('width="600"')
        ->and($svg)->toContain('height="300"')
        ->and($svg)->toContain('rx="16"');
});

test('SVG export renders ellipse', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $exporter = new SvgExporter();
    $svg = $exporter->export($doc->getChildren(), $doc->getVariables());

    expect($svg)->toContain('<ellipse ')
        ->and($svg)->toContain('rx="32"')
        ->and($svg)->toContain('ry="32"');
});

test('SVG export renders text', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $exporter = new SvgExporter();
    $svg = $exporter->export($doc->getChildren(), $doc->getVariables());

    expect($svg)->toContain('<text ')
        ->and($svg)->toContain('Welcome to Pencil')
        ->and($svg)->toContain('font-size="48"');
});

test('SVG export resolves variables', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $exporter = new SvgExporter();
    $svg = $exporter->export($doc->getChildren(), $doc->getVariables());

    // $primary → #3B82F6 and $textColor → #111827
    expect($svg)->toContain('#3B82F6')
        ->and($svg)->toContain('#111827');
});

test('SVG export handles empty elements', function () {
    $doc = PenDocument::create(800, 600);
    $exporter = new SvgExporter();
    $svg = $exporter->export($doc->getChildren(), []);

    expect($svg)->toContain('<svg')
        ->and($svg)->toContain('viewBox="0 0 800 600"');
});

// ─── JSON Export (raw) ──────────────────────────────────────────────

test('JSON export roundtrips', function () use ($fixturePath) {
    $doc = PenDocument::fromFile($fixturePath);
    $json = $doc->toJson();
    $reparsed = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

    expect($reparsed['version'])->toBe('1')
        ->and($reparsed['children'])->toHaveCount(1)
        ->and($reparsed['variables'])->toHaveKey('primary');
});
