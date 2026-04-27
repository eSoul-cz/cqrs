<?php

declare(strict_types=1);

namespace Esoul\Cqrs\Factory;

use Esoul\Cqrs\Contracts\HandlerFactoryInterface;
use RuntimeException;

/**
 * A simple handler factory that instantiates handlers using their class names.
 * This is a basic implementation and does not support dependency injection.
 */
class SimpleHandlerFactory implements HandlerFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function instantiate(string $handlerClass): object
    {
        if (!class_exists($handlerClass)) {
            throw new RuntimeException("Handler class {$handlerClass} does not exist");
        }

        return new $handlerClass();
    }
}
