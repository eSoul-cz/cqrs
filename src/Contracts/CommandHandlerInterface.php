<?php

declare(strict_types=1);

namespace Esoul\Cqrs\Contracts;

interface CommandHandlerInterface
{
    /**
     * @template TResult of mixed Return value of the command
     *
     * @param  CommandInterface<TResult>  $command
     * @return TResult
     */
    public function handle(CommandInterface $command): mixed;
}
