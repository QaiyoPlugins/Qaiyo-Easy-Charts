<?php
/**
 * [qaiyo_chart] shortcode.
 *
 *   [qaiyo_chart id="12"]
 *   [qaiyo_chart data="Landing page;5;;akár 5|Üzleti weboldal;10;15" unit="munkanap" orientation="vertical"]
 *
 * With an id the saved chart is used; any other attribute overrides it.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Frontend;

use Qaiyo\EasyCharts\Chart\ChartConfig;
use Qaiyo\EasyCharts\Chart\Renderer;
use Qaiyo\EasyCharts\Chart\Repository;
use Qaiyo\EasyCharts\Schema;
use Qaiyo\EasyCharts\Support\Cast;

defined( 'ABSPATH' ) || exit;

/**
 * The [qaiyo_chart] shortcode.
 */
final class Shortcode {

	/** Shortcode attribute => configuration key. */
	const ATTRIBUTE_MAP = array(
		'type'             => 'type',
		'orientation'      => 'orientation',
		'title'            => 'title',
		'description'      => 'description',
		'show_title'       => 'show_caption',
		'min'              => 'scale_min',
		'max'              => 'scale_max',
		'step'             => 'step',
		'unit'             => 'unit',
		'unit_position'    => 'unit_position',
		'axis'             => 'show_axis',
		'values'           => 'show_values',
		'separator'        => 'separator',
		'prefix'           => 'prefix',
		'suffix'           => 'suffix',
		'layout'           => 'layout',
		'animate'          => 'animate',
		'effect'           => 'animation',
		'duration'         => 'duration',
		'delay'            => 'stagger',
		'easing'           => 'easing',
		'replay'           => 'replay',
		'count'            => 'count_up',
		'lazy'             => 'lazy',
		'hover'            => 'hover_focus',
		'table'            => 'data_table',
		'color'            => 'bar_color',
		'range_color'      => 'range_color',
		'track_color'      => 'track_color',
		'label_color'      => 'label_color',
		'value_color'      => 'value_color',
		'axis_color'       => 'axis_color',
		'font'             => 'font_family',
		'bar_size'         => 'bar_size',
		'height'           => 'chart_height',
		'radius'           => 'radius',
		'scheme'           => 'scheme',
		'background'       => 'bg_color',
		'dark_color'       => 'dark_bar_color',
		'dark_range_color' => 'dark_range_color',
		'dark_track_color' => 'dark_track_color',
		'dark_label_color' => 'dark_label_color',
		'dark_value_color' => 'dark_value_color',
		'dark_axis_color'  => 'dark_axis_color',
		'dark_background'  => 'dark_bg_color',
		'series_a'         => 'series_a',
		'series_b'         => 'series_b',
		'bands'            => 'bands',
		'patterns'         => 'patterns',
		'tooltips'         => 'tooltips',
		'export'           => 'export',
	);

	/**
	 * Hook this component into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'add' ) );
	}

	/**
	 * Register the shortcode.
	 */
	public function add(): void {
		add_shortcode( Schema::SHORTCODE, array( self::class, 'render' ) );
	}

	/**
	 * Render the output.
	 *
	 * @param mixed $atts Shortcode attributes.
	 */
	public static function render( mixed $atts ): string {
		$atts = array_change_key_case( Cast::arr( $atts ), CASE_LOWER );

		$config = array();
		$id     = Cast::int( $atts['id'] ?? 0 );
		if ( $id > 0 ) {
			$saved = Repository::find( $id );
			if ( null === $saved ) {
				return '';
			}
			$config = $saved;
		} else {
			$config = ChartConfig::defaults();
		}

		foreach ( self::ATTRIBUTE_MAP as $attribute => $key ) {
			if ( array_key_exists( $attribute, $atts ) ) {
				$config[ $key ] = $atts[ $attribute ];
			}
		}

		if ( isset( $atts['data'] ) ) {
			$config['items'] = ChartConfig::parse_data_string( Cast::string( $atts['data'] ) );
		}

		$config = ChartConfig::sanitize( $config );
		if ( array() === $config['items'] ) {
			return '';
		}

		$root = array();
		if ( isset( $atts['class'] ) ) {
			$root['class'] = Cast::string( $atts['class'] );
		}
		if ( isset( $atts['html_id'] ) ) {
			$root['id'] = sanitize_html_class( Cast::string( $atts['html_id'] ) );
		}

		return Renderer::render( $config, $root );
	}
}
