<?php

declare(strict_types=1);

namespace Forge\Container;

use Forge\Contracts\ContainerInterface;

final class Container implements ContainerInterface
{
    private array $bindings = [];
	
	private array $instances = [];

    public function bind(
        string $abstract,
        mixed $concrete
    ): void {
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => false,
        ];
    }

    public function singleton(
        string $abstract,
        mixed $concrete
    ): void {
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => true,
        ];
    }

    public function instance(
		string $abstract,
		mixed $instance
	): void {
		$this->instances[$abstract] = $instance;
	}

    public function make(
    string $abstract
	): mixed {
    if (isset($this->instances[$abstract])) {
        return $this->instances[$abstract];
    }

    $concrete = $this->bindings[$abstract]['concrete']
        ?? $abstract;

    if (is_object($concrete)) {
        return $concrete;
    }

    $reflection = new \ReflectionClass($concrete);

    if (!$reflection->isInstantiable()) {
        throw new ContainerException(
            "Cannot instantiate {$concrete}."
        );
    }

    $constructor = $reflection->getConstructor();

    if ($constructor === null) {
        $instance = $reflection->newInstance();

        if (
            isset($this->bindings[$abstract])
            && $this->bindings[$abstract]['shared'] === true
        ) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    $dependencies = [];

    foreach ($constructor->getParameters() as $parameter) {
        $type = $parameter->getType();

        if ($type === null) {
            throw new ContainerException(
                "Unable to resolve dependency "
                . $parameter->getName()
                . " for {$concrete}."
            );
        }

        if (!$type instanceof \ReflectionNamedType) {
            throw new ContainerException(
                "Unable to resolve dependency "
                . $parameter->getName()
                . " for {$concrete}."
            );
        }

        $dependency = $type->getName();

        $dependencies[] = $this->make($dependency);
    }

    $instance = $reflection->newInstanceArgs(
        $dependencies
    );

    if (
        isset($this->bindings[$abstract])
        && $this->bindings[$abstract]['shared'] === true
    ) {
        $this->instances[$abstract] = $instance;
    }

    return $instance;
	}

    public function has(
		string $abstract
	): bool {
		return isset($this->bindings[$abstract])
			|| isset($this->instances[$abstract]);
	}
}