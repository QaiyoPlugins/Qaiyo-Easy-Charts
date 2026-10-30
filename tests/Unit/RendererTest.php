<?php
/**
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Tests\Unit;

use Qaiyo\EasyCharts\Chart\ChartConfig;
use Qaiyo\EasyCharts\Chart\Renderer;

final class RendererTest extends TestCase {

	/**
	 * @param array<string,mixed> $overrides Config overrides.
	 */
	private function render( array $overrides = array() ): string {
		return Renderer::render( ChartConfig::sanitize( array_merge( ChartConfig::demo(), $overrides ) ), array(), false );
	}

	public function test_scale_rounds_the_automatic_maximum_up_to_the_step(): void {
		$scale = Renderer::scale(
			ChartConfig::sanitize(
				array(
					'items' => array(
						array(
							'label' => 'a',
							'value' => 7,
							'max'   => 17,
						),
					),
					'step'  => 5,
				)
			)
		);

		$this->assertSame( 0.0, $scale['min'] );
		$this->assertSame( 20.0, $scale['max'] );
	}

	public function test_markup_is_an_accessible_figure_with_a_data_table(): void {
		$html = $this->render( array( 'title' => 'Delivery' ) );

		$this->assertStringStartsWith( '<figure class="qec-chart qec-bars qec-orient-horizontal', $html );
		$this->assertStringContainsString( 'aria-label="Delivery"', $html );
		$this->assertStringContainsString( '<div class="qec-grid" aria-hidden="true">', $html );
		$this->assertStringContainsString( '<caption>Delivery</caption>', $html );
		$this->assertStringContainsString( '<td>10–15 munkanap</td>', $html );
		$this->assertStringContainsString( '--qec-start:50%;--qec-size:25%', $html );
	}

	public function test_without_data_table_the_grid_is_a_list(): void {
		$html = $this->render( array( 'data_table' => false ) );

		$this->assertStringContainsString( 'role="list"', $html );
		$this->assertStringNotContainsString( '<table', $html );
	}

	public function test_per_chart_colors_become_root_variables(): void {
		$html = $this->render( array( 'bar_color' => '#ff0000' ) );

		$this->assertStringContainsString( '--qec-primary:#ff0000;', $html );
	}

	public function test_user_text_is_escaped(): void {
		$html = Renderer::render(
			ChartConfig::sanitize(
				array(
					'items' => array(
						array(
							'label' => 'A " onmouseover="x',
							'value' => 1,
							'text'  => '1 & 2',
						),
					),
				)
			),
			array(),
			false
		);

		$this->assertStringNotContainsString( '" onmouseover="x', $html );
		$this->assertStringContainsString( '1 &amp; 2', $html );
	}

	public function test_columns_show_the_unit_as_axis_title(): void {
		$this->assertStringContainsString( '<div class="qec-axis-title" aria-hidden="true">munkanap</div>', $this->render( array( 'orientation' => 'vertical' ) ) );
		$this->assertStringNotContainsString( 'qec-axis-title', $this->render( array( 'orientation' => 'horizontal' ) ) );
	}

	public function test_plain_text_lists_every_value_with_its_unit(): void {
		$text = Renderer::plain_text( ChartConfig::demo() );

		$this->assertStringContainsString( 'Landing page: akár 5 munkanap', $text );
		$this->assertStringContainsString( 'Alap webshop: 10–20 munkanap', $text );
	}
}
