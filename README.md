# esoul-cz/cqrs

Minimal CQRS library for PHP 8.5+ with:

- `CommandBus` for write operations
- `QueryBus` for read operations
- attribute-based handler discovery
- optional discovery result caching
- pluggable handler instantiation through `HandlerFactoryInterface`

The package stays small on purpose. It does not ship with a container integration, middleware pipeline, or framework bindings.

## Requirements

- PHP `>=8.5`

## Installation

Install from Packagist:

```bash
composer require esoul-cz/cqrs
```

If you need to install directly from GitHub instead, add the repository to `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/eSoul-cz/cqrs"
    }
  ]
}
```

## Concepts

Commands and queries are marker interfaces with phpdoc generics:

- `CommandInterface<TResult>`
- `QueryInterface<TResult>`

Handlers implement:

- `CommandHandlerInterface`
- `QueryHandlerInterface`

Each handler exposes a single `handle()` method that receives the corresponding command or query and returns the result.

## Quick Start

### Define a command

```php
<?php

declare(strict_types=1);

namespace App\Orders;

use Esoul\Cqrs\Contracts\CommandInterface;

/**
 * @implements CommandInterface<string>
 */
final readonly class CreateOrder implements CommandInterface
{
    public function __construct(
        public string $number,
    ) {}
}
```

### Define a command handler

```php
<?php

declare(strict_types=1);

namespace App\Orders;

use Esoul\Cqrs\Attributes\HandlesCommand;
use Esoul\Cqrs\Contracts\CommandHandlerInterface;
use Esoul\Cqrs\Contracts\CommandInterface;

#[HandlesCommand(CreateOrder::class)]
final class CreateOrderHandler implements CommandHandlerInterface
{
    /**
     * @param CreateOrder $command
     */
    public function handle(CommandInterface $command): string
    {
        return 'Created order ' . $command->number;
    }
}
```

### Dispatch the command

```php
<?php

use App\Orders\CreateOrder;
use App\Orders\CreateOrderHandler;
use Esoul\Cqrs\Bus\CommandBus;
use Esoul\Cqrs\Factory\SimpleHandlerFactory;

$bus = new CommandBus(new SimpleHandlerFactory());
$bus->registerHandler(CreateOrder::class, CreateOrderHandler::class);

$result = $bus->dispatch(new CreateOrder('ORD-001'));
```

## Queries

Queries work the same way, but use `QueryBus`, `QueryInterface`, `QueryHandlerInterface`, and `#[HandlesQuery(...)]`.

```php
<?php

declare(strict_types=1);

namespace App\Orders;

use Esoul\Cqrs\Attributes\HandlesQuery;
use Esoul\Cqrs\Contracts\QueryHandlerInterface;
use Esoul\Cqrs\Contracts\QueryInterface;

/**
 * @implements QueryInterface<array{id: int, number: string}|null>
 */
final readonly class FindOrderByNumber implements QueryInterface
{
    public function __construct(
        public string $number,
    ) {}
}

#[HandlesQuery(FindOrderByNumber::class)]
final class FindOrderByNumberHandler implements QueryHandlerInterface
{
    /**
     * @param FindOrderByNumber $query
     * @return array{id: int, number: string}|null
     */
    public function handle(QueryInterface $query): ?array
    {
        return ['id' => 1, 'number' => $query->number];
    }
}
```

```php
<?php

use App\Orders\FindOrderByNumber;
use App\Orders\FindOrderByNumberHandler;
use Esoul\Cqrs\Bus\QueryBus;
use Esoul\Cqrs\Factory\SimpleHandlerFactory;

$bus = new QueryBus(new SimpleHandlerFactory());
$bus->registerHandler(FindOrderByNumber::class, FindOrderByNumberHandler::class);

$result = $bus->execute(new FindOrderByNumber('ORD-001'));
```

## Handler Discovery

Both buses can discover handlers from a directory.

### Commands

```php
<?php

use Esoul\Cqrs\Bus\CommandBus;
use Esoul\Cqrs\Factory\SimpleHandlerFactory;

$bus = new CommandBus(new SimpleHandlerFactory());

$bus->discoverHandlers(
    directory: __DIR__ . '/src',
    rootNamespace: '\\App',
    cache: true,
);
```

### Queries

```php
<?php

use Esoul\Cqrs\Bus\QueryBus;
use Esoul\Cqrs\Factory\SimpleHandlerFactory;

$bus = new QueryBus(new SimpleHandlerFactory());

$bus->discoverHandlers(
    directory: __DIR__ . '/src',
    rootNamespace: '\\App',
    cache: true,
);
```

Discovery registers classes that:

- are loadable with `class_exists()`
- implement the matching handler interface
- carry the matching attribute

The discovered mapping is returned as an array:

```php
[
    App\Orders\CreateOrder::class => App\Orders\CreateOrderHandler::class,
]
```

### Repeatable command attributes

`HandlesCommand` is repeatable, so one handler can handle more than one command.

```php
#[HandlesCommand(CreateOrder::class)]
#[HandlesCommand(CancelOrder::class)]
final class OrderMutationHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed
    {
        // ...
    }
}
```

`HandlesQuery` is not repeatable.

## Discovery Cache

Discovery supports file-based caching for filtered class lists.

Set the cache directory once during bootstrap:

```php
<?php

use Esoul\Cqrs\Helpers\Discovery;

Discovery::setCacheDirectory(__DIR__ . '/var/cache/cqrs');
```

When you call `discoverHandlers(..., cache: true)`, the buses use fixed cache keys:

- `command_handlers`
- `query_handlers`

Cache entries are reused only when the cache file is newer than or equal to the newest discovered source file. If matching source files change, discovery rebuilds the cache automatically.

If no cache directory is configured, discovery still works and simply skips caching.

## Handler Instantiation

Both buses depend on `HandlerFactoryInterface`:

```php
<?php

namespace Esoul\Cqrs\Contracts;

interface HandlerFactoryInterface
{
    public function instantiate(string $handlerClass): object;
}
```

The package ships with `SimpleHandlerFactory`, which does plain `new $handlerClass()` construction. Use it only when your handlers do not need dependencies.

For real applications, provide your own factory that delegates to your DI container:

```php
<?php

declare(strict_types=1);

namespace App\Cqrs;

use Esoul\Cqrs\Contracts\HandlerFactoryInterface;
use Psr\Container\ContainerInterface;

final readonly class ContainerHandlerFactory implements HandlerFactoryInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function instantiate(string $handlerClass): object
    {
        return $this->container->get($handlerClass);
    }
}
```

## Failure Modes

The buses throw `RuntimeException` when:

- no handler is registered for a dispatched command
- no handler is registered for an executed query
- the instantiated class does not implement the required handler interface
- `SimpleHandlerFactory` is asked to instantiate a class that does not exist

`Discovery::setCacheDirectory()` also throws `RuntimeException` if the cache directory cannot be created.

## Notes

- Relative discovery directories are resolved from the package root. In applications, prefer absolute paths for clarity.
- Discovery only finds classes already available to the autoloader.
- Handler registration is last-write-wins. Registering the same command or query again replaces the previous handler mapping.
