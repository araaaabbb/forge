<?php

declare(strict_types=1);

namespace Forge\Tests\Unit;

use Forge\Container\Container;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    public function test_container_can_be_instantiated(): void
    {
        $container = new Container();

        self::assertInstanceOf(
            Container::class,
            $container
        );
    }
}