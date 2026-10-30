<?php
/**
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Tests\Unit;

use Qaiyo\EasyCharts\Support\Cast;

final class CastTest extends TestCase {

	public function test_string_never_converts_arrays(): void {
		$this->assertSame( '', Cast::string( array( 'a' ) ) );
		$this->assertSame( '12', Cast::string( 12 ) );
	}

	public function test_bool_accepts_builder_flags(): void {
		foreach ( array( 'yes', 'true', '1', 'on', true, 1 ) as $truthy ) {
			$this->assertTrue( Cast::bool( $truthy ) );
		}
		foreach ( array( 'no', '', '0', 'off', false, 0, null, array() ) as $falsy ) {
			$this->assertFalse( Cast::bool( $falsy ) );
		}
	}

	public function test_number_parses_comma_decimals_and_rejects_garbage(): void {
		$this->assertSame( 10.5, Cast::number( '10,5' ) );
		$this->assertSame( 1000.0, Cast::number( '1 000' ) );
		$this->assertNull( Cast::number( '10px' ) );
		$this->assertNull( Cast::number( '' ) );
		$this->assertNull( Cast::number( INF ) );
	}

	public function test_choice_falls_back_outside_the_allowlist(): void {
		$this->assertSame( 'a', Cast::choice( 'x', array( 'a', 'b' ), 'a' ) );
		$this->assertSame( 'b', Cast::choice( 'b', array( 'a', 'b' ), 'a' ) );
	}
}
