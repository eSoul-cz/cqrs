<?php

declare(strict_types=1);

namespace Esoul\Cqrs\Contracts;

interface CommandBusInterface
{
    /**
     * Dispatches a CQRS command and returns its result
     *
     * @template TResult of mixed Return value of the command
     *
     * @param  CommandInterface<TResult>  $command
     * @return TResult
     */
    public function dispatch(CommandInterface $command): mixed;

    /**
     * @template TResult of mixed Return value of the command
     *
     * @param  class-string<CommandInterface<TResult>>  $commandClass
     * @param  class-string<CommandHandlerInterface>  $handlerClass
     */
    public function registerHandler(string $commandClass, string $handlerClass): void;
}
