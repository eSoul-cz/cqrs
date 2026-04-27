<?php

declare(strict_types=1);

namespace Tests\Stubs\CQRS;

use Esoul\Cqrs\Attributes\HandlesCommand;
use Esoul\Cqrs\Contracts\CommandHandlerInterface;
use Esoul\Cqrs\Contracts\CommandInterface;
use RuntimeException;

#[HandlesCommand(MultiCommandOne::class)]
#[HandlesCommand(MultiCommandTwo::class)]
#[HandlesCommand(MultiCommandThree::class)]
class MultiCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  MultiCommandOne|MultiCommandTwo|MultiCommandThree  $command
     */
    public function handle(CommandInterface $command): string
    {
        if ($command instanceof MultiCommandOne) {
            return 'one';
        }

        if ($command instanceof MultiCommandTwo) {
            return 'two';
        }

        if ($command instanceof MultiCommandThree) {
            return 'three';
        }

        throw new RuntimeException('Unsupported command: ' . $command::class);
    }
}
