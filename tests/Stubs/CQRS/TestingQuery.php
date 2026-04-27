<?php

declare(strict_types=1);

namespace Tests\Stubs\CQRS;

use Esoul\Cqrs\Contracts\QueryInterface;

/**
 * @implements QueryInterface<string>
 */
class TestingQuery implements QueryInterface {}
