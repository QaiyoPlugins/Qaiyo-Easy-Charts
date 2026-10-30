<?php
/**
 * Update safety: charts saved by earlier versions must keep rendering the same.
 *
 * Every new option has a default and every saved configuration goes through
 * ChartConfig::sanitize(), so old data never needs a migration. These tests
 * pin that contract and the public markup (class names people style).
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Tests\Unit;

use Qaiyo\EasyCharts\Chart\ChartConfig;
use Qaiyo\EasyCharts\Chart\Renderer;
use Qaiyo\EasyCharts\Schema;

final class BackwardCompatibilityTest extends TestCase {

	/**
	 * A configuration as the very first release stored it: no chart type, no
	 * animation details, no tooltips, patterns, export or chart ID.
	 *
	 * @return array<string, mixed>
	 */
	private function first_release_config(): array {
		return array(
			'orientation' => 'horizontal',
			'items'       => array(
				array(
					'label' => 'Landing page',
					'value' => 5,
					'max'   => '',
					'text'  => 'akár 5',
				),
				array(
					'label' => 'Üzleti weboldal',
					'value' => 10,
					'max'   => 15,
					'text'  => '',
				),
			),
			'unit'        => 'munkanap',
			'step'        => 5,
			'animate'     => true,
			'bar_color'   => '#0f0c24',
		);
	}

	public function test_old_saved_chart_becomes_a_bars_chart_with_defaults(): void {
		$config = ChartConfig::sanitize( $this->first_release_config() );

		$this->assertSame( 'bars', $config['type'] );
		$this->assertSame( 'grow', $config['animation'] );
		$this->assertFalse( $config['tooltips'] );
		$this->assertFalse( $config['patterns'] );
		$this->assertFalse( $config['export'] );
		$this->assertSame( 15.0, $config['items'][1]['max'] );
	}

	public function test_old_saved_chart_renders_the_same_markup(): void {
		$config         = ChartConfig::sanitize( $this->first_release_config() );
		$config['lazy'] = false;
		$html           = Renderer::render( $config, array(), false );

		// The class names that themes and custom CSS rely on.
		foreach ( array( 'qec-chart', 'qec-bars', 'qec-orient-horizontal', 'qec-grid', 'qec-row', 'qec-label', 'qec-track', 'qec-seg qec-seg--primary', 'qec-seg qec-seg--secondary', 'qec-value', 'qec-axis', 'qec-tick', 'qec-data-table' ) as $class ) {
			$this->assertStringContainsString( $class, $html, $class );
		}
		$this->assertStringContainsString( '--qec-start:66.6667%;--qec-size:33.3333%', $html );
		$this->assertStringContainsString( '>10–15<', $html );
		$this->assertStringContainsString( '<td>akár 5 munkanap</td>', $html );
	}

	public function test_identifiers_stored_in_content_never_change(): void {
		// Stored in posts and page-builder data: renaming any of these would break existing pages.
		$this->assertSame( 'qaiyo_chart', Schema::SHORTCODE );
		$this->assertSame( 'qaiyo-easy-charts/chart', Schema::BLOCK );
		$this->assertSame( 'qaiyo-easy-chart', Schema::ELEMENT );
		$this->assertSame( 'qaiyo_ec_chart', Schema::POST_TYPE );
		$this->assertSame( '_qaiyo_ec_config', Schema::META_CONFIG );
	}

	public function test_unknown_or_broken_values_fall_back_instead_of_failing(): void {
		$config = ChartConfig::sanitize(
			array(
				'type'        => 'pie-from-the-future',
				'orientation' => array( 'nested' ),
				'items'       => 'not a list',
				'duration'    => array(),
				'bar_color'   => 'javascript:alert(1)',
			)
		);

		$this->assertSame( 'bars', $config['type'] );
		$this->assertSame( 'horizontal', $config['orientation'] );
		$this->assertSame( array(), $config['items'] );
		$this->assertSame( 0, $config['duration'] );
		$this->assertSame( '', $config['bar_color'] );
	}
}
