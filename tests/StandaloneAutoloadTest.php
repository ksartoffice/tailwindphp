<?php

declare(strict_types=1);

namespace TailwindPHP\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StandaloneAutoloadTest extends TestCase
{
    /**
     * @test
     */
    public function itLoadsEveryComposerFileAutoload(): void
    {
        $root = dirname(__DIR__);
        $composer = json_decode(file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $expected = array_map(fn (string $file): string => substr($file, strlen('src/')), $composer['autoload']['files']);
        preg_match_all("/'([^']+\\.php)'/", file_get_contents($root . '/autoload.php'), $matches);
        self::assertSame($expected, $matches[1]);
    }

    /**
     * @test
     */
    public function itCompilesThroughTheStandaloneAutoloaderAlone(): void
    {
        $root = dirname(__DIR__);
        $script = 'require ' . var_export($root . '/autoload.php', true) . ';'
            . 'echo \TailwindPHP\Tailwind::generate(\'<div class="flex-grow has-data-[slot=icon]:pl-2">\', \'@import "tailwindcss/utilities.css";\');';
        // Avoid cmd.exe stripping quotes from the PHP source on Windows.
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
        self::assertStringContainsString('flex-grow: 1;', $css);
        self::assertStringContainsString(':has([data-slot="icon"])', $css);
    }
}
