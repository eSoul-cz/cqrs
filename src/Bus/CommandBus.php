<?php

declare(strict_types=1);

namespace Esoul\Cqrs\Bus;

use Esoul\Cqrs\Attributes\HandlesCommand;
use Esoul\Cqrs\Contracts\CommandBusInterface;
use Esoul\Cqrs\Contracts\CommandHandlerInterface;
use Esoul\Cqrs\Contracts\CommandInterface;
use Esoul\Cqrs\Contracts\HandlerFactoryInterface;
use Esoul\Cqrs\Helpers\Discovery;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

final class CommandBus implements CommandBusInterface
{
    /** @var array<class-string<CommandInterface<mixed>>, class-string<CommandHandlerInterface>> */
    private array $handlers = [];

    public function __construct(
        private readonly HandlerFactoryInterface $handlerFactory,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function dispatch(CommandInterface $command): mixed
    {
        // Check if handler is registered for the command
        if (!isset($this->handlers[$command::class])) {
            throw new RuntimeException('No handler registered for command ' . $command::class);
        }

        // Get handler from container
        $handlerClass = $this->handlers[$command::class];
        $handler = $this->handlerFactory->instantiate($handlerClass);
        /** @phpstan-ignore instanceof.alwaysTrue */
        if (!$handler instanceof CommandHandlerInterface) {
            throw new RuntimeException("Handler class {$handlerClass} does not implement CommandHandlerInterface");
        }

        return $handler->handle($command);
    }

    /**
     * {@inheritDoc}
     */
    public function registerHandler(string $commandClass, string $handlerClass): void
    {
        $this->handlers[$commandClass] = $handlerClass;
    }

    /**
     * @return array<class-string<CommandInterface<mixed>>, class-string<CommandHandlerInterface>>
     *
     * @throws ReflectionException
     */
    public function discoverHandlers(string $directory, string $rootNamespace = '\\App', bool $cache = true): array
    {
        // Scan for command handlers using the HandlesCommands attribute and register them with the command bus
        /** @var class-string<CommandHandlerInterface>[] $handlers */
        $handlers = new Discovery($directory, $rootNamespace)
            ->withAttribute(HandlesCommand::class)
            ->implements(CommandHandlerInterface::class)
            ->get($cache ? 'command_handlers' : null);

        foreach ($handlers as $handlerClass) {
            $reflection = new ReflectionClass($handlerClass);
            $attributes = $reflection->getAttributes(HandlesCommand::class, ReflectionAttribute::IS_INSTANCEOF);
            foreach ($attributes as $attribute) {
                /** @var HandlesCommand $handlesCommand */
                $handlesCommand = $attribute->newInstance();
                $this->registerHandler($handlesCommand->commandClass, $handlerClass);
            }
        }

        return $this->handlers;
    }
}
