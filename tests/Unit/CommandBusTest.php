<?php

declare(strict_types=1);

namespace Tests\Unit;

use Esoul\Cqrs\Attributes\HandlesCommand;
use Esoul\Cqrs\Bus\CommandBus;
use Esoul\Cqrs\Factory\SimpleHandlerFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionAttribute;
use ReflectionClass;
use RuntimeException;
use stdClass;
use Tests\Stubs\CQRS\Example\TestCommand;
use Tests\Stubs\CQRS\Example\TestCommandHandler;
use Tests\Stubs\CQRS\MultiCommandHandler;
use Tests\Stubs\CQRS\MultiCommandOne;
use Tests\Stubs\CQRS\MultiCommandThree;
use Tests\Stubs\CQRS\MultiCommandTwo;
use Tests\Stubs\CQRS\TestingCommand;
use Tests\Stubs\CQRS\TestingCommandHandler;

class CommandBusTest extends TestCase
{
    public function test_handler_discovery(): void
    {
        $commandBus = new CommandBus(new SimpleHandlerFactory());

        $handlers = $commandBus->discoverHandlers(dirname(__DIR__) . '/Stubs', '\\Tests\\Stubs', false);

        $this->assertArrayHasKey(TestCommand::class, $handlers);
        $this->assertSame(TestCommandHandler::class, $handlers[TestCommand::class]);
    }

    public function test_same_handler_can_be_registered_from_multiple_handles_command_attributes(): void
    {
        $commandBus = new CommandBus(new SimpleHandlerFactory());
        $reflection = new ReflectionClass(MultiCommandHandler::class);

        $attributes = $reflection->getAttributes(HandlesCommand::class, ReflectionAttribute::IS_INSTANCEOF);

        foreach ($attributes as $attribute) {
            /** @var HandlesCommand $handlesCommand */
            $handlesCommand = $attribute->newInstance();
            $commandBus->registerHandler($handlesCommand->commandClass, MultiCommandHandler::class);
        }

        $this->assertSame('one', $commandBus->dispatch(new MultiCommandOne()));
        $this->assertSame('two', $commandBus->dispatch(new MultiCommandTwo()));
        $this->assertSame('three', $commandBus->dispatch(new MultiCommandThree()));
    }

    #[Group('injection')]
    public function test_dispatch_manual_register(): void
    {
        $commandBus = new CommandBus(new SimpleHandlerFactory());
        $commandBus->registerHandler(TestingCommand::class, TestingCommandHandler::class);

        $command = new TestingCommand();

        $result = $commandBus->dispatch($command);

        $this->assertSame('Handled!', $result);
    }

    #[Group('injection')]
    public function test_dispatch_without_handler(): void
    {
        $commandBus = new CommandBus(new SimpleHandlerFactory());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No handler registered for command ' . TestingCommand::class);
        $commandBus->dispatch(new TestingCommand());
    }

    #[Group('injection')]
    public function test_dispatch_with_invalid_handler(): void
    {
        $commandBus = new CommandBus(new SimpleHandlerFactory());
        /** @phpstan-ignore argument.type */
        $commandBus->registerHandler(TestingCommand::class, stdClass::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Handler class stdClass does not implement CommandHandlerInterface');
        $commandBus->dispatch(new TestingCommand());
    }
}
