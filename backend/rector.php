<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/app',
    ]);

    $rectorConfig->phpVersion(\Rector\ValueObject\PhpVersion::PHP_81);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        SetList::CODE_QUALITY,
        SetList::TYPE_DECLARATION,
        SetList::EARLY_RETURN,
        SetList::DEAD_CODE,
        SetList::NAMING,
    ]);

    $rectorConfig->importNames();
    $rectorConfig->parallel();
    $rectorConfig->cacheDirectory(__DIR__ . '/var/rector');

    $rectorConfig->skip([
        __DIR__ . '/vendor/*',
        __DIR__ . '/public/index.php',
        __DIR__ . '/database/*',
        __DIR__ . '/config/*',
    ]);
};