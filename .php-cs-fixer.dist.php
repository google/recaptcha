<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@auto' => true,
        '@PhpCsFixer' => true,
        'declare_strict_types' => true,
    ])
    ->setFinder(
        (new Finder())
            ->in(__DIR__)
            ->ignoreVCSIgnored(true)
            ->append([__DIR__.'/examples/config.php.dist'])
    )
;
