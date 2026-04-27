<?php

declare(strict_types=1);

namespace Tests\Stubs\CQRS\Example;

use Esoul\Cqrs\Contracts\CommandInterface;

/**
 * @implements CommandInterface<string>
 */
final class TestCommand implements CommandInterface
{
    public function __construct(
        public string $property,
    ) {}
}
