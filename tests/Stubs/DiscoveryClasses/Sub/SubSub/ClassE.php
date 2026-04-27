<?php

declare(strict_types=1);

namespace Tests\Stubs\DiscoveryClasses\Sub\SubSub;

use AllowDynamicProperties;
use JsonSerializable;

#[AllowDynamicProperties]
class ClassE implements JsonSerializable
{
    public function jsonSerialize(): mixed
    {
        return null;
    }
}
