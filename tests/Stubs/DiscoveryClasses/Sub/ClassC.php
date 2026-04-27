<?php

declare(strict_types=1);

namespace Tests\Stubs\DiscoveryClasses\Sub;

use JsonSerializable;

class ClassC implements JsonSerializable
{
    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): mixed
    {
        return null;
    }
}
