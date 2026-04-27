<?php

declare(strict_types=1);

namespace Esoul\Cqrs\Contracts;

interface QueryBusInterface
{
    /**
     * Execute a CQRS query and returns its result
     *
     * @template TResult of mixed Return value of the query
     *
     * @param  QueryInterface<TResult>  $query
     * @return TResult
     */
    public function execute(QueryInterface $query): mixed;

    /**
     * @template TResult of mixed Return value of the query
     *
     * @param  class-string<QueryInterface<TResult>>  $queryClass
     * @param  class-string<QueryHandlerInterface>  $handlerClass
     */
    public function registerHandler(string $queryClass, string $handlerClass): void;
}
