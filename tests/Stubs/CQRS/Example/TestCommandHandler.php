<?php

declare(strict_types=1);

namespace Tests\Stubs\CQRS\Example;

use Esoul\Cqrs\Attributes\HandlesCommand;
use Esoul\Cqrs\Contracts\CommandHandlerInterface;
use Esoul\Cqrs\Contracts\CommandInterface;

#[HandlesCommand(TestCommand::class)]
final class TestCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  TestCommand  $command
     */
    public function handle(CommandInterface $command): string
    {
        return 'Handled command with property: ' . $command->property;
    }
}
