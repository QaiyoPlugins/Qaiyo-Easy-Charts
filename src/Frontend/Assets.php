<?php
/**
 * Front-end chart assets: registered once, enqueued only where a chart renders.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Frontend;

use Qaiyo\EasyCharts\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and enqueues the front-end chart assets.
 */
final class Assets {

	/**
	 * Hook this component into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'register_handles' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_early' ) );
	}

	/**
	 * Cache-busting version: plugin version plus file modification time.
	 *
	 * @param string $relative Relative.
	 */
	public static function version( string $relative ): string {
		$path  = QAIYO_EC_PLUGIN_DIR . $relative;
		$mtime = is_readable( $path ) ? filemtime( $path ) : false;
		return QAIYO_EC_VERSION . ( false !== $mtime ? '.' . $mtime : '' );
	}

	/**
	 * Register the chart stylesheet and script.
	 */
	public static function register_handles(): void {
		wp_register_style(
			Schema::HANDLE_CHART,
			QAIYO_EC_PLUGIN_URL . 'assets/css/chart.css',
			array(),
			self::version( 'assets/css/chart.css' )
		);

		wp_register_script(
			Schema::HANDLE_CHART,
			QAIYO_EC_PLUGIN_URL . 'assets/js/chart.js',
			array(),
			self::version( 'assets/js/chart.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Enqueue on demand (called by every renderer).
	 */
	public static function enqueue(): void {
		if ( ! wp_style_is( Schema::HANDLE_CHART, 'registered' ) ) {
			self::register_handles();
		}
		wp_enqueue_style( Schema::HANDLE_CHART );
		wp_enqueue_script( Schema::HANDLE_CHART );
	}

	/**
	 * When the current post visibly contains a chart, load the stylesheet in the
	 * <head> instead of the footer, so the chart never flashes unstyled.
	 */
	public function enqueue_early(): void {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}
		if ( has_shortcode( $post->post_content, Schema::SHORTCODE ) || has_block( Schema::BLOCK, $post ) ) {
			self::enqueue();
		}
	}
}
