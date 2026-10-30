<?php
/**
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Tests\Unit;

use Qaiyo\EasyCharts\Support\Css;

final class CssTest extends TestCase {

	/**
	 * @return array<string,array{0:mixed,1:string}>
	 */
	public function colors(): array {
		return array(
			'hex'            => array( '#0F0C24', '#0f0c24' ),
			'short hex'      => array( '#fff', '#fff' ),
			'rgba'           => array( 'rgba(15, 12, 36, .5)', 'rgba(15, 12, 36, .5)' ),
			'variable'       => array( 'var(--brand)', 'var(--brand)' ),
			'keyword'        => array( 'transparent', 'transparent' ),
			'bricks array'   => array( array( 'hex' => '#123456' ), '#123456' ),
			'css injection'  => array( 'red;}body{display:none', '' ),
			'url injection'  => array( 'url(javascript:alert(1))', '' ),
			'quote breakout' => array( '#fff" onmouseover="x', '' ),
		);
	}

	/**
	 * @dataProvider colors
	 *
	 * @param mixed $input Input.
	 */
	public function test_color( $input, string $expected ): void {
		$this->assertSame( $expected, Css::color( $input ) );
	}

	public function test_length(): void {
		$this->assertSame( '28px', Css::length( '28' ) );
		$this->assertSame( '1.5em', Css::length( '1.5em' ) );
		$this->assertSame( '', Css::length( '10px;color:red' ) );
		$this->assertSame( '', Css::length( 'calc(1px)' ) );
	}

	public function test_font_family(): void {
		$this->assertSame( 'DM Sans, sans-serif', Css::font_family( 'DM Sans, sans-serif' ) );
		$this->assertSame( 'var(--wp--preset--font-family--inter)', Css::font_family( 'var(--wp--preset--font-family--inter)' ) );
		$this->assertSame( '', Css::font_family( 'Arial; } body { x' ) );
	}

	public function test_percent_is_locale_independent(): void {
		$this->assertSame( '0%', Css::percent( 0.0 ) );
		$this->assertSame( '25%', Css::percent( 25.0 ) );
		$this->assertSame( '33.3333%', Css::percent( 100 / 3 ) );
	}
}
