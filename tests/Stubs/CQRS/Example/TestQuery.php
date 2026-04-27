<?php

declare(strict_types=1);

namespace Tests\Stubs\CQRS\Example;

use Esoul\Cqrs\Contracts\QueryInterface;

/**
 * @implements QueryInterface<string>
 */
final class TestQuery implements QueryInterface
{
    public private(set) ?int $filter1 = null;

    public function filter1(int $filter1): self
    {
        $this->filter1 = $filter1;

        return $this;
    }
}
