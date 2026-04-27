<?php

declare(strict_types=1);

namespace Tests\Stubs\CQRS;

use Esoul\Cqrs\Contracts\CommandInterface;

/**
 * @implements CommandInterface<string>
 */
class MultiCommandOne implements CommandInterface {}
