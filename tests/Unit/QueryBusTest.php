<?php

declare(strict_types=1);

namespace Tests\Unit;

use Esoul\Cqrs\Bus\QueryBus;
use Esoul\Cqrs\Factory\SimpleHandlerFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Tests\Stubs\CQRS\Example\TestQuery;
use Tests\Stubs\CQRS\Example\TestQueryHandler;
use Tests\Stubs\CQRS\TestingQuery;
use Tests\Stubs\CQRS\TestingQueryHandler;

class QueryBusTest extends TestCase
{
    public function test_handler_discovery(): void
    {
        $queryBus = new QueryBus(new SimpleHandlerFactory());

        $handlers = $queryBus->discoverHandlers(dirname(__DIR__) . '/Stubs', '\\Tests\\Stubs', false);

        $this->assertArrayHasKey(TestQuery::class, $handlers);
        $this->assertSame(TestQueryHandler::class, $handlers[TestQuery::class]);
    }

    #[Group('injection')]
    public function test_dispatch_manual_register(): void
    {
        $queryBus = new QueryBus(new SimpleHandlerFactory());
        $queryBus->registerHandler(TestingQuery::class, TestingQueryHandler::class);

        $query = new TestingQuery();

        $result = $queryBus->execute($query);

        $this->assertSame('Handled!', $result);
    }

    #[Group('injection')]
    public function test_dispatch_without_handler(): void
    {
        $queryBus = new QueryBus(new SimpleHandlerFactory());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No handler registered for query ' . TestingQuery::class);
        $queryBus->execute(new TestingQuery());
    }

    #[Group('injection')]
    public function test_dispatch_with_invalid_handler(): void
    {
        $queryBus = new QueryBus(new SimpleHandlerFactory());
        /** @phpstan-ignore argument.type */
        $queryBus->registerHandler(TestingQuery::class, stdClass::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Handler class stdClass does not implement QueryHandlerInterface');
        $queryBus->execute(new TestingQuery());
    }
}
