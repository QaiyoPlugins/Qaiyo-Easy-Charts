<?php
/**
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Tests\Unit;

use Brain\Monkey\Functions;
use Qaiyo\EasyCharts\Chart\ChartConfig;
use Qaiyo\EasyCharts\Chart\Renderer;
use Qaiyo\EasyCharts\Stats\Views;

final class ViewsTest extends TestCase {

	public function test_prune_keeps_only_the_last_30_days(): void {
		$days = Views::prune(
			array(
				'2026-10-30' => 4,
				'2026-10-01' => 2,
				'2026-09-30' => 9,
				'2026-11-02' => 1,
			),
			'2026-10-30'
		);

		$this->assertSame(
			array(
				'2026-10-01' => 2,
				'2026-10-30' => 4,
			),
			$days
		);
	}

	public function test_stats_fill_missing_days_with_zero(): void {
		$today = gmdate( 'Y-m-d' );
		$week  = gmdate( 'Y-m-d', time() - 3 * DAY_IN_SECONDS );
		$old   = gmdate( 'Y-m-d', time() - 20 * DAY_IN_SECONDS );

		Functions\when( 'get_post_meta' )->alias(
			static fn( int $id, string $key ) => Views::META_TOTAL === $key
				? '57'
				: array(
					$today => 3,
					$week  => 2,
					$old   => 5,
					'junk' => 99,
				)
		);

		$stats = Views::stats( 7 );

		$this->assertSame( 57, $stats['total'] );
		$this->assertSame( 5, $stats['week'] );
		$this->assertSame( 10, $stats['month'] );
		$this->assertCount( Views::DAYS, $stats['days'] );
		$this->assertSame( 3, $stats['days'][ $today ] );
	}

	public function test_inline_charts_are_never_tracked(): void {
		$this->assertFalse( Views::should_track( 0 ) );

		$html = Renderer::render( ChartConfig::demo(), array(), false );
		$this->assertStringNotContainsString( 'data-qec-view', $html );
	}

	public function test_saved_chart_is_tracked_for_visitors_only(): void {
		Functions\when( 'current_user_can' )->justReturn( false );
		$this->assertTrue( Views::should_track( 12 ) );

		$config             = ChartConfig::demo();
		$config['chart_id'] = 12;
		$this->assertStringContainsString( 'data-qec-view="12"', Renderer::render( $config, array(), false ) );
	}

	public function test_editors_are_not_tracked_by_default(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		$this->assertFalse( Views::should_track( 12 ) );
	}

	public function test_chart_id_is_sanitized(): void {
		$this->assertSame( 0, ChartConfig::sanitize( array( 'chart_id' => -4 ) )['chart_id'] );
		$this->assertSame( 9, ChartConfig::sanitize( array( 'chart_id' => '9' ) )['chart_id'] );
	}
}
