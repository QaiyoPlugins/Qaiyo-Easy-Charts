<?php
/**
 * The one chart configuration contract.
 *
 * Every entry point (saved chart, shortcode, block, Elementor, Bricks, Abilities)
 * turns its own input into this array through sanitize(), so validation lives
 * in exactly one place and every output path renders the same markup.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Chart;

use Qaiyo\EasyCharts\Admin\Settings;
use Qaiyo\EasyCharts\Schema;
use Qaiyo\EasyCharts\Support\Cast;
use Qaiyo\EasyCharts\Support\Css;

defined( 'ABSPATH' ) || exit;

/**
 * Defaults, validation and parsing of chart configurations.
 *
 * @phpstan-type QecItem array{label: string, value: float, max: float|string, text: string, color: string, range_color: string}
 * @phpstan-type QecConfig array{orientation: string, items: list<QecItem>, title: string, description: string, show_caption: bool, scale_min: float, scale_max: float|string, step: float|string, unit: string, unit_position: string, show_axis: bool, show_values: bool, separator: string, prefix: string, suffix: string, layout: string, animate: bool, hover_focus: bool, data_table: bool, bar_color: string, range_color: string, track_color: string, label_color: string, value_color: string, axis_color: string, font_family: string, bar_size: string, chart_height: string, radius: string}
 */
final class ChartConfig {

	const ORIENTATIONS   = array( 'horizontal', 'vertical', 'auto' );
	const LAYOUTS        = array( 'auto', 'inline', 'stacked' );
	const UNIT_POSITIONS = array( 'last', 'all', 'none' );

	/** Hard cap on rows, so a single chart can never become unbounded work. */
	const MAX_ITEMS = 100;

	/**
	 * The default look of a chart. Every chart, block and builder element shows
	 * these as its own editable values; there is no site-wide colour setting,
	 * because each chart is styled on its own. Mirrors the defaults in chart.css.
	 */
	const DEFAULT_STYLE = array(
		'bar_color'   => '#0f0c24',
		'range_color' => '#95e6d8',
		'track_color' => '#ecebf1',
		'label_color' => '#0f0c24',
		'value_color' => '#0f0c24',
		'axis_color'  => '#6b6880',
	);

	/** Style keys that become CSS custom properties on the chart root. */
	const STYLE_VARS = array(
		'bar_color'    => '--qec-primary',
		'range_color'  => '--qec-secondary',
		'track_color'  => '--qec-track',
		'label_color'  => '--qec-label-color',
		'value_color'  => '--qec-value-color',
		'axis_color'   => '--qec-axis-color',
		'font_family'  => '--qec-font-family',
		'bar_size'     => '--qec-bar-size',
		'chart_height' => '--qec-chart-height',
		'radius'       => '--qec-bar-radius',
	);

	/**
	 * Defaults, seeded from the global settings.
	 *
	 * @return array<mixed>
	 */
	public static function defaults(): array {
		$settings = Settings::get();

		$defaults = array(
			'orientation'   => 'horizontal',
			'items'         => array(),
			'title'         => '',
			'description'   => '',
			'show_caption'  => false,
			'scale_min'     => 0,
			'scale_max'     => '',
			'step'          => 5,
			'unit'          => '',
			'unit_position' => 'last',
			'show_axis'     => true,
			'show_values'   => true,
			'separator'     => '–',
			'prefix'        => '',
			'suffix'        => '',
			'layout'        => 'auto',
			'animate'       => $settings['animate'],
			'hover_focus'   => $settings['hover_focus'],
			'data_table'    => $settings['data_table'],
			'bar_color'     => '',
			'range_color'   => '',
			'track_color'   => '',
			'label_color'   => '',
			'value_color'   => '',
			'axis_color'    => '',
			'font_family'   => '',
			'bar_size'      => '',
			'chart_height'  => '',
			'radius'        => '',
		);

		/**
		 * Filters the default chart configuration.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string,mixed> $defaults Default configuration.
		 */
		$filtered = apply_filters( 'qaiyo_easy_charts_default_config', $defaults );

		// Callbacks are third-party code: narrow the result instead of trusting it.
		return array_merge( $defaults, Cast::arr( $filtered ) );
	}

	/**
	 * Example rows used when a chart is created.
	 *
	 * @return list<array<string,mixed>>
	 */
	public static function demo_items(): array {
		return array(
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
			array(
				'label' => 'Alap webshop',
				'value' => 10,
				'max'   => 20,
				'text'  => '',
			),
		);
	}

	/**
	 * Example configuration for new charts and freshly dropped widgets.
	 *
	 * @return QecConfig
	 */
	public static function demo(): array {
		return self::sanitize(
			array_merge(
				self::DEFAULT_STYLE,
				array(
					'items' => self::demo_items(),
					'unit'  => 'munkanap',
				)
			)
		);
	}

	/**
	 * Validate any raw input into a complete configuration. Unknown keys are
	 * dropped; missing keys take their default.
	 *
	 * @param array<mixed> $raw Raw configuration.
	 * @return QecConfig
	 */
	public static function sanitize( array $raw ): array {
		$defaults = self::defaults();
		$raw      = array_merge( $defaults, $raw );

		$config = array(
			'orientation'   => Cast::choice( $raw['orientation'], self::ORIENTATIONS, 'horizontal' ),
			'items'         => self::sanitize_items( Cast::arr( $raw['items'] ) ),
			'title'         => self::text( $raw['title'], 200 ),
			'description'   => self::text( $raw['description'], 500 ),
			'show_caption'  => Cast::bool( $raw['show_caption'] ),
			'scale_min'     => self::number_or_default( $raw['scale_min'], 0 ),
			'scale_max'     => self::number_or_empty( $raw['scale_max'] ),
			'step'          => self::number_or_empty( $raw['step'] ),
			'unit'          => self::text( $raw['unit'], 60 ),
			'unit_position' => Cast::choice( $raw['unit_position'], self::UNIT_POSITIONS, 'last' ),
			'show_axis'     => Cast::bool( $raw['show_axis'] ),
			'show_values'   => Cast::bool( $raw['show_values'] ),
			'separator'     => self::text( $raw['separator'], 10, '–' ),
			'prefix'        => self::text( $raw['prefix'], 40 ),
			'suffix'        => self::text( $raw['suffix'], 40 ),
			'layout'        => Cast::choice( $raw['layout'], self::LAYOUTS, 'auto' ),
			'animate'       => Cast::bool( $raw['animate'] ),
			'hover_focus'   => Cast::bool( $raw['hover_focus'] ),
			'data_table'    => Cast::bool( $raw['data_table'] ),
			'bar_color'     => Css::color( $raw['bar_color'] ),
			'range_color'   => Css::color( $raw['range_color'] ),
			'track_color'   => Css::color( $raw['track_color'] ),
			'label_color'   => Css::color( $raw['label_color'] ),
			'value_color'   => Css::color( $raw['value_color'] ),
			'axis_color'    => Css::color( $raw['axis_color'] ),
			'font_family'   => Css::font_family( $raw['font_family'] ),
			'bar_size'      => Css::length( $raw['bar_size'] ),
			'chart_height'  => Css::length( $raw['chart_height'] ),
			'radius'        => Css::length( $raw['radius'] ),
		);

		return $config;
	}

	/**
	 * Sanitize items.
	 *
	 * @param array<mixed> $items Raw rows.
	 * @return list<QecItem>
	 */
	public static function sanitize_items( array $items ): array {
		$clean = array();
		foreach ( $items as $item ) {
			if ( count( $clean ) >= self::MAX_ITEMS ) {
				break;
			}
			$item  = Cast::arr( $item );
			$value = Cast::number( $item['value'] ?? null );
			$label = self::text( $item['label'] ?? '', 200 );
			if ( null === $value && '' === $label ) {
				continue;
			}

			$max = Cast::number( $item['max'] ?? null );

			$clean[] = array(
				'label'       => $label,
				'value'       => null === $value ? 0.0 : $value,
				'max'         => ( null !== $max && null !== $value && $max > $value ) ? $max : '',
				'text'        => self::text( $item['text'] ?? '', 80 ),
				'color'       => Css::color( $item['color'] ?? '' ),
				'range_color' => Css::color( $item['range_color'] ?? '' ),
			);
		}
		return $clean;
	}

	/**
	 * Parse the shortcode's compact data format:
	 * rows separated by "|" or new lines, fields by ";" — label;value;range end;text.
	 *
	 * @param string $data Rows separated by "|" or new lines, fields by ";".
	 * @return list<array{label: string, value: string, max: string, text: string}>
	 */
	public static function parse_data_string( string $data ): array {
		$items = array();
		$rows  = preg_split( '/\||\r\n|\n/', $data );
		foreach ( false === $rows ? array() : $rows as $row ) {
			$row = trim( $row );
			if ( '' === $row ) {
				continue;
			}
			$fields  = array_map( 'trim', explode( ';', $row ) );
			$items[] = array(
				'label' => $fields[0],
				'value' => $fields[1] ?? '',
				'max'   => $fields[2] ?? '',
				'text'  => $fields[3] ?? '',
			);
		}
		return $items;
	}

	/**
	 * Sanitize a text value and cap its length.
	 *
	 * @param mixed  $value Any value.
	 * @param int    $max_length Max length.
	 * @param string $fallback Fallback.
	 */
	private static function text( $value, int $max_length, string $fallback = '' ): string {
		$text = sanitize_text_field( Cast::string( $value ) );
		if ( function_exists( 'mb_substr' ) ) {
			$text = mb_substr( $text, 0, $max_length );
		}
		return '' === $text ? $fallback : $text;
	}

	/**
	 * A number, or an empty string.
	 *
	 * @param mixed $value Any value.
	 * @return float|string Number, or '' when empty/invalid.
	 */
	private static function number_or_empty( $value ) {
		$number = Cast::number( $value );
		return null === $number ? '' : $number;
	}

	/**
	 * A number, or the default.
	 *
	 * @param mixed $value Any value.
	 * @param float $fallback Value used when $value is not a number.
	 */
	private static function number_or_default( $value, float $fallback ): float {
		$number = Cast::number( $value );
		return null === $number ? $fallback : $number;
	}
}
