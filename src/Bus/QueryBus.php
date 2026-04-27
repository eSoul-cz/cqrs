<?php

declare(strict_types=1);

namespace Esoul\Cqrs\Bus;

use Esoul\Cqrs\Attributes\HandlesQuery;
use Esoul\Cqrs\Contracts\HandlerFactoryInterface;
use Esoul\Cqrs\Contracts\QueryBusInterface;
use Esoul\Cqrs\Contracts\QueryHandlerInterface;
use Esoul\Cqrs\Contracts\QueryInterface;
use Esoul\Cqrs\Helpers\Discovery;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

final class QueryBus implements QueryBusInterface
{
    /** @var array<class-string<QueryInterface<mixed>>, class-string<QueryHandlerInterface>> */
    private array $handlers = [];

    public function __construct(
        private readonly HandlerFactoryInterface $handlerFactory,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function execute(QueryInterface $query): mixed
    {
        // Check if handler is registered for the query
        if (!isset($this->handlers[$query::class])) {
            throw new RuntimeException('No handler registered for query ' . $query::class);
        }

        // Get handler from container
        $handlerClass = $this->handlers[$query::class];
        $handler = $this->handlerFactory->instantiate($handlerClass);
        /** @phpstan-ignore instanceof.alwaysTrue */
        if (!$handler instanceof QueryHandlerInterface) {
            throw new RuntimeException("Handler class {$handlerClass} does not implement QueryHandlerInterface");
        }

        return $handler->handle($query);
    }

    /**
     * {@inheritDoc}
     */
    public function registerHandler(string $queryClass, string $handlerClass): void
    {
        $this->handlers[$queryClass] = $handlerClass;
    }

    /**
     * @return array<class-string<QueryInterface<mixed>>, class-string<QueryHandlerInterface>>
     *
     * @throws ReflectionException
     */
    public function discoverHandlers(string $directory, string $rootNamespace = '\\App', bool $cache = true): array
    {
        // Scan for query handlers using the HandlesQuery attribute and register them with the query bus
        /** @var class-string<QueryHandlerInterface>[] $handlers */
        $handlers = new Discovery($directory, $rootNamespace)
            ->withAttribute(HandlesQuery::class)
            ->implements(QueryHandlerInterface::class)
            ->get($cache ? 'query_handlers' : null);

        foreach ($handlers as $handlerClass) {
            $reflection = new ReflectionClass($handlerClass);
            $attributes = $reflection->getAttributes(HandlesQuery::class, ReflectionAttribute::IS_INSTANCEOF);
            foreach ($attributes as $attribute) {
                /** @var HandlesQuery $handlesQuery */
                $handlesQuery = $attribute->newInstance();
                $this->registerHandler($handlesQuery->queryClass, $handlerClass);
            }
        }

        return $this->handlers;
    }
}
