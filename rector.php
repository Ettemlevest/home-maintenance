<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withPhpSets()
    ->withComposerBased(laravel: true)
    ->withSetProviders(LaravelSetProvider::class)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        earlyReturn: true,
    );
