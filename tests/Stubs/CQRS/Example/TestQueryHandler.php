<?php

declare(strict_types=1);

namespace Tests\Stubs\CQRS\Example;

use Esoul\Cqrs\Attributes\HandlesQuery;
use Esoul\Cqrs\Contracts\QueryHandlerInterface;
use Esoul\Cqrs\Contracts\QueryInterface;

#[HandlesQuery(TestQuery::class)]
final class TestQueryHandler implements QueryHandlerInterface
{
    /**
     * @param  TestQuery  $query
     */
    public function handle(QueryInterface $query): string
    {
        return 'filter: ' . ($query->filter1 ?? 'null');
    }
}
