<?php

declare(strict_types = 1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withAutoloadPaths([
        __DIR__ . '/vendor/autoload.php',
    ])
    ->withPhpLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0)
    ->withTypeCoverageLevel(0);
