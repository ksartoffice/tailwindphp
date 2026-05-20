<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;

use function TailwindPHP\Utils\isColor;

/**
 * Tests for is-color.php.
 *
 * @port-deviation:tests These tests are PHP-specific additions for complete coverage.
 */
class is_color extends TestCase
{
    // ==================================================
    // Hex colors
    // ==================================================

    /**
 * @test
 */
    public function hex_3_digit(): void
    {
        $this->assertTrue(isColor('#fff'));
        $this->assertTrue(isColor('#000'));
        $this->assertTrue(isColor('#abc'));
    }

    /**
 * @test
 */
    public function hex_6_digit(): void
    {
        $this->assertTrue(isColor('#ffffff'));
        $this->assertTrue(isColor('#000000'));
        $this->assertTrue(isColor('#abcdef'));
    }

    /**
 * @test
 */
    public function hex_8_digit_alpha(): void
    {
        $this->assertTrue(isColor('#ffffff80'));
        $this->assertTrue(isColor('#00000000'));
    }

    // ==================================================
    // Named colors
    // ==================================================

    /**
 * @test
 */
    public function named_color_red(): void
    {
        $this->assertTrue(isColor('red'));
    }

    /**
 * @test
 */
    public function named_color_blue(): void
    {
        $this->assertTrue(isColor('blue'));
    }

    /**
 * @test
 */
    public function named_color_transparent(): void
    {
        $this->assertTrue(isColor('transparent'));
    }

    /**
 * @test
 */
    public function named_color_currentcolor(): void
    {
        $this->assertTrue(isColor('currentcolor'));
        $this->assertTrue(isColor('currentColor'));
    }

    /**
 * @test
 */
    public function named_color_rebeccapurple(): void
    {
        $this->assertTrue(isColor('rebeccapurple'));
    }

    // ==================================================
    // CSS color functions
    // ==================================================

    /**
 * @test
 */
    public function rgb_function(): void
    {
        $this->assertTrue(isColor('rgb(255, 0, 0)'));
        $this->assertTrue(isColor('rgb(255 0 0)'));
    }

    /**
 * @test
 */
    public function rgba_function(): void
    {
        $this->assertTrue(isColor('rgba(255, 0, 0, 0.5)'));
        $this->assertTrue(isColor('rgba(255 0 0 / 50%)'));
    }

    /**
 * @test
 */
    public function hsl_function(): void
    {
        $this->assertTrue(isColor('hsl(0, 100%, 50%)'));
        $this->assertTrue(isColor('hsl(0 100% 50%)'));
    }

    /**
 * @test
 */
    public function hsla_function(): void
    {
        $this->assertTrue(isColor('hsla(0, 100%, 50%, 0.5)'));
    }

    /**
 * @test
 */
    public function hwb_function(): void
    {
        $this->assertTrue(isColor('hwb(0 0% 0%)'));
    }

    /**
 * @test
 */
    public function lab_function(): void
    {
        $this->assertTrue(isColor('lab(50% 0 0)'));
    }

    /**
 * @test
 */
    public function lch_function(): void
    {
        $this->assertTrue(isColor('lch(50% 0 0)'));
    }

    /**
 * @test
 */
    public function oklab_function(): void
    {
        $this->assertTrue(isColor('oklab(50% 0 0)'));
    }

    /**
 * @test
 */
    public function oklch_function(): void
    {
        $this->assertTrue(isColor('oklch(50% 0 0)'));
    }

    /**
 * @test
 */
    public function color_function(): void
    {
        $this->assertTrue(isColor('color(srgb 1 0 0)'));
    }

    /**
 * @test
 */
    public function color_mix_function(): void
    {
        $this->assertTrue(isColor('color-mix(in oklab, red 50%, blue)'));
    }

    /**
 * @test
 */
    public function light_dark_function(): void
    {
        $this->assertTrue(isColor('light-dark(white, black)'));
    }

    // ==================================================
    // System colors
    // ==================================================

    /**
 * @test
 */
    public function system_color_canvas(): void
    {
        $this->assertTrue(isColor('canvas'));
    }

    /**
 * @test
 */
    public function system_color_buttonface(): void
    {
        $this->assertTrue(isColor('buttonface'));
    }

    // ==================================================
    // Non-colors
    // ==================================================

    /**
 * @test
 */
    public function non_color_empty(): void
    {
        $this->assertFalse(isColor(''));
    }

    /**
 * @test
 */
    public function non_color_random_string(): void
    {
        $this->assertFalse(isColor('hello'));
        $this->assertFalse(isColor('notacolor'));
    }

    /**
 * @test
 */
    public function non_color_number(): void
    {
        $this->assertFalse(isColor('123'));
        $this->assertFalse(isColor('1.5'));
    }

    /**
 * @test
 */
    public function non_color_unit(): void
    {
        $this->assertFalse(isColor('10px'));
        $this->assertFalse(isColor('1rem'));
    }
}
