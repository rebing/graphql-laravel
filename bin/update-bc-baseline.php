#!/usr/bin/env php
<?php

declare(strict_types = 1);

/**
 * Manages the baseline in .roave-backward-compatibility-check.xml.
 *
 * Two modes:
 *   --strip-baseline  Remove the <baseline> section from the XML (preserves everything else).
 *                     Use before running the BC checker so it reports all breaks.
 *
 *   (default)         Read BC checker output from stdin, extract [BC] lines, and
 *                     write/replace the <baseline> section in the XML.
 */
$baselineFile = \dirname(__DIR__) . '/.roave-backward-compatibility-check.xml';
$schemaUrl = 'https://raw.githubusercontent.com/Roave/BackwardCompatibilityCheck/8.21.x/Resources/schema.xsd';

if (\in_array('--strip-baseline', $argv, true)) {
    if (!file_exists($baselineFile)) {
        exit(0);
    }

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = true;
    $dom->preserveWhiteSpace = false;
    $loaded = $dom->load($baselineFile);

    if (!$loaded) {
        fwrite(STDERR, "Error: Failed to parse baseline file: $baselineFile\n");

        exit(1);
    }

    $root = $dom->documentElement;

    if (null === $root || 'roave-bc-check' !== $root->tagName) {
        fwrite(STDERR, "Error: Unexpected root element in baseline file\n");

        exit(1);
    }

    $baselines = $root->getElementsByTagName('baseline');
    $toRemove = [];

    for ($i = 0; $i < $baselines->length; $i++) {
        $toRemove[] = $baselines->item($i);
    }

    if ([] === $toRemove) {
        exit(0);
    }

    foreach ($toRemove as $node) {
        $root->removeChild($node);
    }

    $xml = $dom->saveXML();

    if (false === $xml) {
        fwrite(STDERR, "Error: Failed to generate XML\n");

        exit(1);
    }

    file_put_contents($baselineFile, $xml);

    exit(0);
}

$input = stream_get_contents(STDIN);

if (false === $input) {
    fwrite(STDERR, "Error: Failed to read from stdin\n");

    exit(1);
}

$lines = explode("\n", $input);

$breaks = [];

foreach ($lines as $line) {
    $trimmed = trim($line);

    if (str_starts_with($trimmed, '[BC]')) {
        $breaks[] = $trimmed;
    }
}

if ([] === $breaks) {
    fwrite(STDOUT, "No [BC] breaks found in input. Nothing to write.\n");

    if (file_exists($baselineFile)) {
        fwrite(STDOUT, "Note: Existing baseline file left unchanged: $baselineFile\n");
        fwrite(STDOUT, "      You may want to remove it since there are no breaks to suppress.\n");
    }

    exit(0);
}

$regexes = [];

foreach ($breaks as $break) {
    $regexes[] = '#' . preg_quote($break, '#') . '#';
}

// Sort for stable output
sort($regexes);
$regexes = array_unique($regexes);

$dom = new DOMDocument('1.0', 'UTF-8');
$dom->formatOutput = true;
$dom->preserveWhiteSpace = false;

if (file_exists($baselineFile)) {
    $loaded = $dom->load($baselineFile);

    if (!$loaded) {
        fwrite(STDERR, "Error: Failed to parse existing baseline file: $baselineFile\n");

        exit(1);
    }

    $root = $dom->documentElement;

    if (null === $root || 'roave-bc-check' !== $root->tagName) {
        fwrite(STDERR, "Error: Unexpected root element in baseline file\n");

        exit(1);
    }

    // Remove existing <baseline> elements
    $existingBaselines = $root->getElementsByTagName('baseline');

    // Collect nodes first, then remove (can't modify during iteration)
    $toRemove = [];

    for ($i = 0; $i < $existingBaselines->length; $i++) {
        $toRemove[] = $existingBaselines->item($i);
    }

    foreach ($toRemove as $node) {
        $root->removeChild($node);
    }
} else {
    $root = $dom->createElement('roave-bc-check');
    $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $root->setAttribute('xsi:noNamespaceSchemaLocation', $schemaUrl);
    $dom->appendChild($root);
}

$baseline = $dom->createElement('baseline');

foreach ($regexes as $regex) {
    $entry = $dom->createElement('ignored-regex', $regex);
    $baseline->appendChild($entry);
}

$root->appendChild($baseline);

$xml = $dom->saveXML();

if (false === $xml) {
    fwrite(STDERR, "Error: Failed to generate XML\n");

    exit(1);
}

file_put_contents($baselineFile, $xml);

$count = \count($regexes);
fwrite(STDOUT, "Wrote $count baseline entries to $baselineFile\n");
