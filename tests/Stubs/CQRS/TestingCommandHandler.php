<?php

declare(strict_types=1);

namespace Tests\Stubs\CQRS;

use Esoul\Cqrs\Contracts\CommandHandlerInterface;
use Esoul\Cqrs\Contracts\CommandInterface;

class TestingCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  TestingCommand  $command
     */
    public function handle(CommandInterface $command): string
    {
        return 'Handled!';
    }
}
