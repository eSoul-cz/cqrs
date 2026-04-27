<?php

declare(strict_types=1);

namespace Esoul\Cqrs\Attributes;

use Attribute;
use Esoul\Cqrs\Contracts\QueryInterface;

#[Attribute(Attribute::TARGET_CLASS)]
class HandlesQuery
{
    /**
     * @template TQueryResult of mixed Return value of the query
     *
     * @param  class-string<QueryInterface<TQueryResult>>  $queryClass
     */
    public function __construct(
        public string $queryClass
    ) {}

}
