<?php

declare(strict_types=1);

namespace Esoul\Cqrs\Contracts;

interface HandlerFactoryInterface
{
    /**
     * @template T of CommandHandlerInterface|QueryHandlerInterface
     *
     * @param  class-string<T>  $handlerClass
     * @return T
     */
    public function instantiate(string $handlerClass): object;
}
