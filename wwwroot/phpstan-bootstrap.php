<?php

declare(strict_types=1);

/**
 * Custom bootstrap for PHPStan CI4 extension.
 *
 * Loads only CI4 system helpers — NOT the framework's demo app/
 * which conflicts with the project's own app/ Config, Models, etc.
 */

// Only load system helpers, not the framework's app/ directory
require_once __DIR__ . '/vendor/codeigniter4/framework/system/util_bootstrap.php';

$helperDir = __DIR__ . '/vendor/codeigniter4/framework/system/Helpers';

if (is_dir($helperDir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($helperDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY | RecursiveIteratorIterator::CHILD_FIRST,
    );

    /** @var SplFileInfo $file */
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            require_once $file->getRealPath();
        }
    }
}
