<?php
/**
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Tests\Unit;

use Qaiyo\EasyCharts\Chart\ChartConfig;

final class ChartConfigTest extends TestCase {

	public function test_sanitize_fills_every_key_and_drops_unknown_ones(): void {
		$config = ChartConfig::sanitize( array( 'evil' => 'x' ) );

		$this->assertArrayNotHasKey( 'evil', $config );
		$this->assertSame( array_keys( ChartConfig::defaults() ), array_keys( $config ) );
		$this->assertSame( 'horizontal', $config['orientation'] );
	}

	public function test_invalid_choices_fall_back(): void {
		$config = ChartConfig::sanitize(
			array(
				'orientation'   => 'diagonal',
				'layout'        => 'x',
				'unit_position' => 'y',
			)
		);

		$this->assertSame( 'horizontal', $config['orientation'] );
		$this->assertSame( 'auto', $config['layout'] );
		$this->assertSame( 'last', $config['unit_position'] );
	}

	public function test_items_are_validated(): void {
		$items = ChartConfig::sanitize_items(
			array(
				array(
					'label' => '<b>Shop</b>',
					'value' => '10',
					'max'   => '20',
				),
				array(
					'label' => 'Range below value is dropped',
					'value' => '10',
					'max'   => '5',
				),
				array(
					'label' => '',
					'value' => '',
				),
				'not an array',
			)
		);

		$this->assertCount( 2, $items );
		$this->assertSame( 'Shop', $items[0]['label'] );
		$this->assertSame( 20.0, $items[0]['max'] );
		$this->assertSame( '', $items[1]['max'] );
	}

	public function test_items_are_capped(): void {
		$rows = array_fill(
			0,
			ChartConfig::MAX_ITEMS + 20,
			array(
				'label' => 'x',
				'value' => 1,
			)
		);

		$this->assertCount( ChartConfig::MAX_ITEMS, ChartConfig::sanitize_items( $rows ) );
	}

	public function test_parse_data_string(): void {
		$items = ChartConfig::parse_data_string( "Landing page;5;;akár 5|Shop;10;15\nWebshop;10;20" );

		$this->assertCount( 3, $items );
		$this->assertSame( 'akár 5', $items[0]['text'] );
		$this->assertSame( '15', $items[1]['max'] );
		$this->assertSame( 'Webshop', $items[2]['label'] );
	}

	public function test_demo_chart_carries_its_own_colors(): void {
		$demo = ChartConfig::demo();

		foreach ( ChartConfig::DEFAULT_STYLE as $key => $color ) {
			$this->assertSame( $color, $demo[ $key ] );
		}
		$this->assertCount( 3, $demo['items'] );
	}
}
