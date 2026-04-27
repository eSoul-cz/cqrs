<?php

declare(strict_types=1);

namespace Tests\Stubs\CQRS;

use Esoul\Cqrs\Contracts\QueryHandlerInterface;
use Esoul\Cqrs\Contracts\QueryInterface;

class TestingQueryHandler implements QueryHandlerInterface
{
    /**
     * @param  TestingQuery  $query
     */
    public function handle(QueryInterface $query): string
    {
        return 'Handled!';
    }
}
