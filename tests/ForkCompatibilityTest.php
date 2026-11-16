<?php

declare(strict_types=1);

namespace TailwindPHP\Tests;

use PHPUnit\Framework\TestCase;

use function TailwindPHP\merge;

final class ForkCompatibilityTest extends TestCase
{
    public static function legacyConflictPairs(): array
    {
        return [
            ['bg-gradient-to-r', 'bg-linear-to-l'],
            ['flex-grow', 'grow-0'],
            ['flex-grow-[2]', 'grow-[3]'],
            ['flex-shrink', 'shrink-0'],
            ['decoration-slice', 'box-decoration-clone'],
            ['overflow-ellipsis', 'text-clip'],
            ['hover:flex-grow', 'hover:grow-0'],
            ['flex-grow!', 'grow-0!'],
        ];
    }

    /** @dataProvider legacyConflictPairs */
    public function test_legacy_and_modern_aliases_obey_last_class_precedence(string $legacy, string $modern): void
    {
        self::assertSame($modern, merge($legacy, $modern));
        self::assertSame($legacy, merge($modern, $legacy));
    }

    public function test_legacy_aliases_keep_unrelated_utilities_and_different_variants(): void
    {
        self::assertSame('flex-grow hover:grow-0 text-red-500', merge('flex-grow', 'hover:grow-0', 'text-red-500'));
        self::assertSame('decoration-red-500 box-decoration-clone', merge('decoration-red-500', 'decoration-slice', 'box-decoration-clone'));
    }
}
