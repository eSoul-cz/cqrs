<?php

declare(strict_types=1);

namespace Esoul\Cqrs\Contracts;

interface QueryHandlerInterface
{
    /**
     * @template TResult of mixed Return value of the query
     *
     * @param  QueryInterface<TResult>  $query
     * @return TResult
     */
    public function handle(QueryInterface $query): mixed;
}
