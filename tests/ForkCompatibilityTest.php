<?php

declare(strict_types=1);

namespace TailwindPHP\Tests;

use PHPUnit\Framework\TestCase;

use function TailwindPHP\compile;
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

    public static function legacyScreenBreakpoints(): array
    {
        return [['xl', '80rem'], ['custom', '96rem']];
    }

    /** @dataProvider legacyScreenBreakpoints */
    public function test_max_width_screen_aliases_reject_modifiers(string $breakpoint, string $width): void
    {
        $css = '@theme { --breakpoint-xl: 80rem; --breakpoint-custom: 96rem; } @tailwind utilities;';
        $alias = 'max-w-screen-' . $breakpoint;
        $compiled = compile($css, ['loadDefaultTheme' => false]);
        self::assertSame('', $compiled['build']([
            $alias . '/foo',
            $alias . '/[foo]',
            $alias . '/0',
            $alias . '/[0]',
            'hover:' . $alias . '/foo',
        ]));

        $valid = compile($css, ['loadDefaultTheme' => false]);
        $output = $valid['build']([$alias]);
        self::assertStringContainsString('max-width: var(--breakpoint-' . $breakpoint . ');', $output);
        self::assertStringContainsString('--breakpoint-' . $breakpoint . ': ' . $width . ';', $output);
    }

    public function test_base_styles_compile_without_development_dependencies(): void
    {
        // A separate standalone process must not inherit Composer's dev polyfills.
        $script = 'require ' . var_export(dirname(__DIR__) . '/autoload.php', true) . ';' . <<<'PHP'
        $compiled = \TailwindPHP\compile('@plugin "@tailwindcss/forms"; @import "tailwindcss/utilities.css";', ['loadDefaultTheme' => false]);
        echo $compiled['build']([]);

        $api = new \TailwindPHP\Plugin\PluginAPI(new \TailwindPHP\Theme(), new \TailwindPHP\Utilities\Utilities(), new \TailwindPHP\Variants\Variants());
        $api->addBase([
            '.fallback' => ['display' => ['block', 'grid']],
            '.repeat' => [['color' => 'red'], ['color' => 'blue']],
            '.empty' => [],
        ]);
        echo \TailwindPHP\Ast\toCss($api->getBaseAst());
        PHP;

        $process = proc_open([PHP_BINARY, '-r', $script], [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['redirect', 1],
        ], $pipes);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $css = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $status = proc_close($process);

        self::assertSame(0, $status, $css);
        self::assertStringContainsString('@layer base', $css);
        self::assertStringContainsString("input:where([type='text'])", $css);
        self::assertStringContainsString(".fallback {\n    display: block;\n    display: grid;\n  }", $css);
        self::assertStringContainsString(".repeat {\n    color: red;\n  }", $css);
        self::assertStringContainsString(".repeat {\n    color: blue;\n  }", $css);
    }
}
