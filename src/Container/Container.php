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

    $hasBinding = isset($this->bindings[$abstract]);

	$concrete = $hasBinding
		? $this->bindings[$abstract]['concrete']
		: $abstract;

    if (is_object($concrete)) {
        return $concrete;
    }

	if (
		!$hasBinding
		&& interface_exists($concrete)
	) {
		throw new NotFoundException(
			"No binding found for {$abstract}."
		);
	}

	if (
		!class_exists($concrete)
		&& !interface_exists($concrete)
	) {
		throw new NotFoundException(
			"No binding found for {$abstract}."
		);
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
		
		if ($type instanceof \ReflectionUnionType) {
			$dependencies[] = $this->resolveUnionType(
				$type,
				$parameter,
				$concrete
			);

			continue;
		}

        if (!$type instanceof \ReflectionNamedType) {
            throw new ContainerException(
                "Unable to resolve dependency "
                . $parameter->getName()
                . " for {$concrete}."
            );
        }
		
		if ($type->isBuiltin()) {
			if ($parameter->isDefaultValueAvailable()) {
				$dependencies[] = $parameter->getDefaultValue();

				continue;
			}

			throw new ContainerException(
				"Unable to resolve primitive dependency \$"
				. $parameter->getName()
				. " for {$concrete}."
			);
		}

        $dependency = $type->getName();

		try {
			$dependencies[] = $this->make($dependency);
		} catch (NotFoundException $exception) {
			if ($parameter->isDefaultValueAvailable()) {
				$dependencies[] = $parameter->getDefaultValue();

				continue;
			}

			throw $exception;
		}
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
	
	private function resolveUnionType(
		\ReflectionUnionType $type,
		\ReflectionParameter $parameter,
		string $concrete
	): mixed {
		$resolved = [];

		foreach ($type->getTypes() as $candidate) {
			if (!$candidate instanceof \ReflectionNamedType) {
				continue;
			}

			if ($candidate->isBuiltin()) {
				continue;
			}

			try {
				$resolved[] = $this->make(
					$candidate->getName()
				);
			} catch (NotFoundException) {
				continue;
			}
		}

		if (count($resolved) === 1) {
			return $resolved[0];
		}

		if (count($resolved) > 1) {
			throw new ContainerException(
				"Unable to resolve union dependency \$"
				. $parameter->getName()
				. " for {$concrete}: multiple candidates are available."
			);
		}

		throw new ContainerException(
			"Unable to resolve union dependency \$"
			. $parameter->getName()
			. " for {$concrete}: no candidate is available."
		);
	}

    public function has(
		string $abstract
	): bool {
		return isset($this->bindings[$abstract])
			|| isset($this->instances[$abstract]);
	}
}