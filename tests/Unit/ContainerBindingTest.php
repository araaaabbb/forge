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
	
	public function test_container_rejects_unresolvable_primitive_dependency(): void
	{
		$container = new Container();

		$this->expectException(
			\Forge\Container\ContainerException::class
		);

		$container->make(
			ConfiguredService::class
		);
	}
	
	public function test_container_uses_default_value_for_primitive_dependency(): void
	{
		$container = new Container();

		$service = $container->make(
			DefaultValueService::class
		);

		self::assertInstanceOf(
			DefaultValueService::class,
			$service
		);

		self::assertSame(
			'Forge',
			$service->name()
		);
	}
	
	public function test_container_uses_null_for_nullable_dependency_with_default(): void
	{
		$container = new Container();

		$service = $container->make(
			OptionalLoggerService::class
		);

		self::assertInstanceOf(
			OptionalLoggerService::class,
			$service
		);

		self::assertNull(
			$service->logger()
		);
	}
	
	public function test_container_resolves_nullable_dependency_when_binding_exists(): void
	{
		$container = new Container();

		$container->bind(
			LoggerInterface::class,
			FileLogger::class
		);

		$service = $container->make(
			OptionalLoggerService::class
		);

		self::assertInstanceOf(
			OptionalLoggerService::class,
			$service
		);

		self::assertInstanceOf(
			FileLogger::class,
			$service->logger()
		);
	}
	
	public function test_container_does_not_silently_use_null_for_required_nullable_dependency(): void
	{
		$container = new Container();

		$this->expectException(
			\Forge\Container\NotFoundException::class
		);

		$container->make(
			RequiredNullableService::class
		);
	}
	
	public function test_container_can_resolve_union_type_when_one_candidate_is_available(): void
	{
		$container = new Container();

		$container->bind(
			LoggerInterface::class,
			FileLogger::class
		);

		$service = $container->make(
			UnionDependencyService::class
		);

		self::assertInstanceOf(
			UnionDependencyService::class,
			$service
		);

		self::assertInstanceOf(
			FileLogger::class,
			$service->dependency()
		);
	}
	
	public function test_container_rejects_union_type_when_no_candidate_is_available(): void
	{
		$container = new Container();

		$this->expectException(
			\Forge\Container\ContainerException::class
		);

		$container->make(
			UnresolvableUnionService::class
		);
	}
	
	public function test_container_rejects_union_type_when_multiple_candidates_are_available(): void
	{
		$container = new Container();

		$container->bind(
			LoggerInterface::class,
			FileLogger::class
		);

		$container->bind(
			CacheInterface::class,
			FileCache::class
		);

		$this->expectException(
			\Forge\Container\ContainerException::class
		);

		$container->make(
			AmbiguousUnionService::class
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

final class ConfiguredService
{
    public function __construct(
        string $name
    ) {
    }
}

final class DefaultValueService
{
    public function __construct(
        private string $name = 'Forge'
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }
}

final class OptionalLoggerService
{
    public function __construct(
        private ?LoggerInterface $logger = null
    ) {
    }

    public function logger(): ?LoggerInterface
    {
        return $this->logger;
    }
}

final class RequiredNullableService
{
    public function __construct(
        private ?LoggerInterface $logger
    ) {
    }

    public function logger(): ?LoggerInterface
    {
        return $this->logger;
    }
}

interface CacheInterface
{
}

final class FileCache implements CacheInterface
{
}

final class UnionDependencyService
{
    public function __construct(
        private LoggerInterface|CacheInterface $dependency
    ) {
    }

    public function dependency(): LoggerInterface|CacheInterface
    {
        return $this->dependency;
    }
}

final class UnresolvableUnionService
{
    public function __construct(
        private LoggerInterface|CacheInterface $dependency
    ) {
    }
}

final class AmbiguousUnionService
{
    public function __construct(
        private LoggerInterface|CacheInterface $dependency
    ) {
    }
}