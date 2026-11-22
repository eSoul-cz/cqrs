<?php

declare(strict_types=1);

use ESoul\CodingStandard\Config;
use PhpCsFixer\Finder;

return Config::create(
    Finder::create()->files()->in([__DIR__ . '/src', __DIR__ . '/tests'])->append([__FILE__]),
    __DIR__ . '/cache/php-cs-fixer.cache',
);
