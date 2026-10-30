<?php
/**
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Tests\Unit;

use Qaiyo\EasyCharts\Chart\ChartConfig;
use Qaiyo\EasyCharts\Chart\Renderer;

final class ChartTypesTest extends TestCase {

	/**
	 * @param string              $type      Chart type.
	 * @param string              $data      Rows in the shortcode format.
	 * @param array<string,mixed> $overrides Config overrides.
	 */
	private function render( string $type, string $data, array $overrides = array() ): string {
		$config = ChartConfig::sanitize(
			array_merge(
				array(
					'type'  => $type,
					'items' => ChartConfig::parse_data_string( $data ),
					'lazy'  => false,
				),
				$overrides
			)
		);
		return Renderer::render( $config, array(), false );
	}

	public function test_unknown_type_falls_back_to_bars_and_only_bars_stand_up(): void {
		$this->assertSame( 'bars', ChartConfig::sanitize( array( 'type' => 'pie' ) )['type'] );

		$ring = ChartConfig::sanitize(
			array(
				'type'        => 'ring',
				'orientation' => 'vertical',
			)
		);
		$this->assertSame( 'horizontal', $ring['orientation'] );
	}

	public function test_second_value_may_be_lower_except_for_ranges_and_timelines(): void {
		$items = ChartConfig::parse_data_string( 'A;10;8' );

		$this->assertSame( '', ChartConfig::sanitize_items( $items, 'bars' )[0]['max'] );
		$this->assertSame( '', ChartConfig::sanitize_items( $items, 'timeline' )[0]['max'] );
		$this->assertSame( 8.0, ChartConfig::sanitize_items( $items, 'compare' )[0]['max'] );
		$this->assertSame( 8.0, ChartConfig::sanitize_items( $items, 'bullet' )[0]['max'] );
	}

	public function test_bands_are_parsed_sorted_and_capped(): void {
		$this->assertSame( array( 50.0, 75.0, 100.0 ), ChartConfig::sanitize( array( 'bands' => '100, 50;75' ) )['bands'] );
		$this->assertCount( ChartConfig::MAX_BANDS, ChartConfig::sanitize( array( 'bands' => '1 2 3 4 5 6' ) )['bands'] );
		$this->assertSame( array(), ChartConfig::sanitize( array( 'bands' => 'none' ) )['bands'] );
	}

	public function test_compare_draws_two_series_with_a_legend_and_named_table_columns(): void {
		$html = $this->render(
			'compare',
			'Landing;5;8',
			array(
				'series_a' => 'Us',
				'series_b' => 'Market',
			)
		);

		$this->assertStringContainsString( 'qec-type-compare qec-scheme-light qec-bars', $html );
		$this->assertStringContainsString( 'qec-seg--a', $html );
		$this->assertStringContainsString( 'qec-seg--b', $html );
		$this->assertStringContainsString( '<div class="qec-legend" aria-hidden="true">', $html );
		$this->assertStringContainsString( '<th scope="col">Us</th><th scope="col">Market</th>', $html );
	}

	public function test_ring_fills_to_the_goal_or_to_100(): void {
		$html = $this->render( 'ring', 'Done;75|Sales;30;60' );

		$this->assertStringContainsString( '--qec-p:75;', $html );
		$this->assertStringContainsString( '--qec-p:50;', $html );
		$this->assertStringContainsString( 'pathLength="100"', $html );
		$this->assertStringContainsString( '<td>50%</td>', $html );
	}

	public function test_timeline_without_finish_is_a_milestone(): void {
		$html = $this->render( 'timeline', 'Design;2;5|Launch;10', array( 'scale_max' => 10 ) );

		$this->assertStringContainsString( '--qec-start:20%;--qec-size:30%', $html );
		$this->assertStringContainsString( 'qec-milestone" style="--qec-pos:100%"', $html );
		$this->assertStringContainsString( '<th scope="col">Start</th><th scope="col">Finish</th>', $html );
	}

	public function test_bullet_draws_bands_measure_and_target(): void {
		$html = $this->render(
			'bullet',
			'Score;72;85',
			array(
				'bands'     => '50, 75, 100',
				'scale_max' => 100,
			)
		);

		$this->assertSame( 3, substr_count( $html, 'class="qec-band"' ) );
		$this->assertStringContainsString( 'qec-seg--measure', $html );
		$this->assertStringContainsString( 'qec-target" style="--qec-pos:85%"', $html );
	}

	public function test_stacked_splits_by_share_and_skips_a_duplicate_percentage(): void {
		$html = $this->render( 'stacked', 'A;3|B;1' );
		$this->assertStringContainsString( '--qec-start:0%;--qec-size:75%', $html );
		$this->assertStringContainsString( '<span class="qec-share qec-count">75%</span>', $html );

		$percent = $this->render( 'stacked', 'A;60|B;40', array( 'suffix' => '%' ) );
		$this->assertStringNotContainsString( 'qec-share', $percent );
	}

	public function test_rating_calculates_the_weighted_average(): void {
		$html = $this->render( 'rating', '5;3|4;1|1;0', array( 'unit' => 'ratings' ) );

		$this->assertStringContainsString( '>4.8</span>', $html );
		$this->assertStringContainsString( '4 ratings', $html );
		$this->assertStringContainsString( 'Average: 4.8 / 5', $html );
		$this->assertSame( 10, substr_count( $html, '<svg viewBox="0 0 24 24"' ) );
	}

	public function test_tooltips_make_marks_focusable_list_items(): void {
		$html = $this->render( 'bars', 'A;5', array( 'tooltips' => true ) );

		$this->assertStringContainsString( 'qec-tooltips', $html );
		$this->assertStringContainsString( '<div class="qec-grid" role="list">', $html );
		$this->assertStringContainsString( 'role="listitem" tabindex="0"', $html );
		$this->assertStringContainsString( '<span class="qec-tip" aria-hidden="true">A: 5</span>', $html );
		$this->assertStringContainsString( '<span class="qec-sr-only">A: 5</span>', $html );
	}

	public function test_without_tooltips_the_drawing_is_hidden_behind_the_table(): void {
		$html = $this->render( 'bars', 'A;5' );

		$this->assertStringContainsString( '<div class="qec-grid" aria-hidden="true">', $html );
		$this->assertStringNotContainsString( 'tabindex', $html );
		$this->assertStringNotContainsString( 'qec-tip', $html );
	}

	public function test_patterns_and_export_buttons(): void {
		$html = $this->render(
			'bars',
			'A;5',
			array(
				'patterns' => true,
				'export'   => true,
			)
		);

		$this->assertStringContainsString( 'qec-patterns', $html );
		$this->assertStringContainsString( 'data-qec-export="png"', $html );
		$this->assertStringContainsString( 'data-qec-export="svg"', $html );
	}

	public function test_style_of_copies_the_look_but_not_the_data(): void {
		$style = ChartConfig::style_of( ChartConfig::demo() );

		$this->assertArrayHasKey( 'bar_color', $style );
		$this->assertArrayHasKey( 'animation', $style );
		$this->assertArrayNotHasKey( 'items', $style );
		$this->assertArrayNotHasKey( 'title', $style );
	}

	public function test_plain_text_includes_the_rating_note(): void {
		$text = Renderer::plain_text( ChartConfig::sanitize( array( 'type' => 'rating', 'items' => ChartConfig::parse_data_string( '5;1|4;1' ) ) ) );

		$this->assertStringContainsString( '5: Count 1, Share 50%', $text );
		$this->assertStringContainsString( 'Average: 4.5 / 5', $text );
	}

	public function test_entrance_animation_always_ends_within_five_seconds(): void {
		$items = str_repeat( 'Row;5|', 40 );

		// 40 rows × the default 140 ms would run for 6.9 s: the delay is shortened.
		$config = ChartConfig::sanitize( array( 'items' => ChartConfig::parse_data_string( $items ) ) );
		$this->assertSame( '88ms', Renderer::stagger( $config ) );

		// Few rows keep the stylesheet default; an explicit delay is kept when it fits.
		$this->assertSame( '', Renderer::stagger( ChartConfig::sanitize( array( 'items' => ChartConfig::parse_data_string( 'A;1|B;2' ) ) ) ) );
		$this->assertSame( '300ms', Renderer::stagger( ChartConfig::sanitize( array( 'items' => ChartConfig::parse_data_string( 'A;1|B;2' ), 'stagger' => 300 ) ) ) );

		// The longest duration still leaves room for counting up.
		$this->assertLessThanOrEqual( 5000, 1.4 * ChartConfig::sanitize( array( 'duration' => 99999 ) )['duration'] );
	}

	public function test_colour_scheme_and_dark_colours(): void {
		$this->assertSame( 'light', ChartConfig::sanitize( array( 'scheme' => 'neon' ) )['scheme'] );

		$html = $this->render(
			'bars',
			'A;5',
			array(
				'scheme'         => 'auto',
				'dark_bar_color' => '#ffcc00',
				'bg_color'       => '#fafafa',
				'dark_bg_color'  => 'transparent',
			)
		);

		$this->assertStringContainsString( 'qec-scheme-auto', $html );
		$this->assertStringContainsString( 'data-qec-scheme="auto"', $html );
		$this->assertStringContainsString( '--qec-dark-primary:#ffcc00;', $html );
		$this->assertStringContainsString( '--qec-bg:#fafafa;', $html );
		$this->assertStringContainsString( '--qec-pad:24px;', $html );
		$this->assertStringContainsString( '--qec-dark-pad:0px;', $html );
	}

	public function test_item_colours_use_their_own_variables(): void {
		$config          = ChartConfig::sanitize( array( 'items' => array( array( 'label' => 'A', 'value' => 1, 'color' => '#ff0000' ) ), 'lazy' => false ) );
		$html            = Renderer::render( $config, array(), false );

		$this->assertStringContainsString( '--qec-item-primary:#ff0000;', $html );
	}
}
