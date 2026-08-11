<?php

declare(strict_types=1);

namespace Forge\Tests\Unit;

use Forge\Forge;
use PHPUnit\Framework\TestCase;

final class FrameworkTest extends TestCase
{
    public function test_framework_test_suite_is_working(): void
    {
        self::assertTrue(true);
    }

    public function test_psr4_autoloading_is_working(): void
    {
        $forge = new Forge();

        self::assertSame(
            'Forge',
            $forge->name()
        );
    }
}