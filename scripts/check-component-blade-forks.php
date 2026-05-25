#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Fail when shared component Blade templates branch on post type / query context.
 *
 * Usage: php scripts/check-component-blade-forks.php
 */

$themeRoot = dirname(__DIR__);
$componentsDir = $themeRoot . '/resources/views/components';

if (! is_dir($componentsDir)) {
    fwrite(STDERR, "Components directory missing: {$componentsDir}\n");
    exit(1);
}

/** @var list<string> */
$forbiddenPatterns = [
    'get_post_type\s*\(',
];

$files = glob($componentsDir . '/*.blade.php') ?: [];
$violations = [];

foreach ($files as $file) {
    $contents = (string) file_get_contents($file);
    $relative = str_replace($themeRoot . '/', '', $file);

    foreach ($forbiddenPatterns as $pattern) {
        if (preg_match('/' . $pattern . '/', $contents) === 1) {
            $violations[] = $relative . ' matches ' . $pattern;
        }
    }
}

if ($violations !== []) {
    fwrite(STDERR, "Component Blade must not fork on page/post context. Use ACF display options instead.\n");
    foreach ($violations as $line) {
        fwrite(STDERR, "  - {$line}\n");
    }
    exit(1);
}

fwrite(STDOUT, "OK — no context forks in component Blade templates.\n");
exit(0);
