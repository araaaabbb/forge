<?php

declare(strict_types=1);

namespace Forge\Tests\Unit;

use Forge\Container\Container;
use PHPUnit\Framework\TestCase;

final class ContainerBindingTest extends TestCase
{
    public function test_container_can_bind_concrete_class(): void
    {
        $container = new Container();

        $container->bind(
            ExampleService::class,
            ExampleService::class
        );

        self::assertTrue(
            $container->has(ExampleService::class)
        );
    }

    public function test_container_can_resolve_concrete_class(): void
    {
        $container = new Container();

        $service = $container->make(
            ExampleService::class
        );

        self::assertInstanceOf(
            ExampleService::class,
            $service
        );
    }
	
	public function test_container_can_resolve_interface_to_implementation(): void
	{
		$container = new Container();

		$container->bind(
			LoggerInterface::class,
			FileLogger::class
		);

		$logger = $container->make(
			LoggerInterface::class
		);

		self::assertInstanceOf(
			FileLogger::class,
			$logger
		);

		self::assertInstanceOf(
			LoggerInterface::class,
			$logger
		);
	}
	
	public function test_container_throws_exception_when_interface_is_not_bound(): void
	{
		$container = new Container();

		$this->expectException(
			\Forge\Container\ContainerException::class
		);

		$container->make(
			LoggerInterface::class
		);
	}
	
	public function test_container_can_autowire_constructor_dependencies(): void
	{
		$container = new Container();

		$service = $container->make(
			UserService::class
		);

		self::assertInstanceOf(
			UserService::class,
			$service
		);

		self::assertInstanceOf(
			UserRepository::class,
			$service->repository()
		);
	}
	
	public function test_container_can_autowire_interface_dependency(): void
	{
		$container = new Container();

		$container->bind(
			UserRepositoryInterface::class,
			MySQLUserRepository::class
		);

		$service = $container->make(
			UserServiceWithInterface::class
		);

		self::assertInstanceOf(
			UserServiceWithInterface::class,
			$service
		);

		self::assertInstanceOf(
			MySQLUserRepository::class,
			$service->repository()
		);
	}
	
	public function test_container_returns_same_instance_for_singleton(): void
	{
		$container = new Container();

		$container->singleton(
			ExampleService::class,
			ExampleService::class
		);

		$first = $container->make(
			ExampleService::class
		);

		$second = $container->make(
			ExampleService::class
		);

		self::assertSame(
			$first,
			$second
		);
	}
	
	public function test_container_can_register_existing_instance(): void
	{
		$container = new Container();

		$service = new ExampleService();

		$container->instance(
			ExampleService::class,
			$service
		);

		$resolved = $container->make(
			ExampleService::class
		);

		self::assertSame(
			$service,
			$resolved
		);
	}
	
	public function test_container_has_returns_true_for_registered_instance(): void
	{
		$container = new Container();

		$service = new ExampleService();

		$container->instance(
			ExampleService::class,
			$service
		);

		self::assertTrue(
			$container->has(
				ExampleService::class
			)
		);
	}
	
	public function test_container_creates_new_instance_for_normal_binding(): void
	{
		$container = new Container();

		$container->bind(
			ExampleService::class,
			ExampleService::class
		);

		$first = $container->make(
			ExampleService::class
		);

		$second = $container->make(
			ExampleService::class
		);

		self::assertNotSame(
			$first,
			$second
		);
	}
}

final class ExampleService
{
}

interface LoggerInterface
{
}

final class FileLogger implements LoggerInterface
{
}

final class UserRepository
{
}

final class UserService
{
    public function __construct(
        private UserRepository $repository
    ) {
    }

    public function repository(): UserRepository
    {
        return $this->repository;
    }
}

interface UserRepositoryInterface
{
}

final class MySQLUserRepository implements UserRepositoryInterface
{
}

final class UserServiceWithInterface
{
    public function __construct(
        private UserRepositoryInterface $repository
    ) {
    }

    public function repository(): UserRepositoryInterface
    {
        return $this->repository;
    }
}