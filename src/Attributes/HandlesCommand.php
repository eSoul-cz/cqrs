<?php

declare(strict_types=1);

namespace Esoul\Cqrs\Attributes;

use Attribute;
use Esoul\Cqrs\Contracts\CommandInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class HandlesCommand
{
    /**
     * @template TCommandResult of mixed Return value of the command
     *
     * @param  class-string<CommandInterface<TCommandResult>>  $commandClass
     */
    public function __construct(
        public string $commandClass
    ) {}

}
