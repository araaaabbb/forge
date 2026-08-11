<?php

declare(strict_types=1);

namespace Forge\Contracts;

interface ContainerInterface
{
    public function bind(
        string $abstract,
        mixed $concrete
    ): void;

    public function singleton(
        string $abstract,
        mixed $concrete
    ): void;

    public function instance(
        string $abstract,
        mixed $instance
    ): void;

    public function make(
        string $abstract
    ): mixed;

    public function has(
        string $abstract
    ): bool;
}