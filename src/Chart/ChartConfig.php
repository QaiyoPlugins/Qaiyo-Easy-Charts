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
 * @phpstan-type QecTable array{head: list<string>, rows: list<non-empty-list<string>>, note: string}
 * @phpstan-type    QecConfig array{type: string, chart_id: int, orientation: string, items: list<QecItem>, title: string, description: string, show_caption: bool, scale_min: float, scale_max: float|string, step: float|string, unit: string, unit_position: string, show_axis: bool, show_values: bool, separator: string, prefix: string, suffix: string, layout: string, animate: bool, animation: string, duration: int, stagger: int, easing: string, replay: bool, count_up: bool, lazy: bool, hover_focus: bool, data_table: bool, bar_color: string, range_color: string, track_color: string, label_color: string, value_color: string, axis_color: string, font_family: string, bar_size: string, chart_height: string, radius: string, series_a: string, series_b: string, bands: list<float>, patterns: bool, tooltips: bool, export: bool}
 */
final class ChartConfig {

	/**
	 * Chart types. Every type reads the same rows (label, value, second value,
	 * text, colours); the second value means: bars — range end, compare — the
	 * second series, ring — the goal, timeline — the finish, bullet — the target.
	 */
	const TYPES = array( 'bars', 'compare', 'ring', 'timeline', 'bullet', 'stacked', 'rating' );

	/** Types drawn as rows on a shared value axis. */
	const ROW_TYPES = array( 'bars', 'compare', 'timeline', 'bullet' );

	/** At most this many qualitative bands in a bullet chart. */
	const MAX_BANDS = 4;

	const ORIENTATIONS   = array( 'horizontal', 'vertical', 'auto' );
	const LAYOUTS        = array( 'auto', 'inline', 'stacked' );
	const UNIT_POSITIONS = array( 'last', 'all', 'none' );
	const ANIMATIONS     = array( 'grow', 'fade', 'rise' );
	const EASINGS        = array( 'smooth', 'linear', 'spring' );

	/**
	 * Animation timing bounds, in milliseconds. 0 = the stylesheet default.
	 * With counting up (1.4 × duration) the longest animation stays under 5 s.
	 */
	const MAX_DURATION = 3500;
	const MAX_STAGGER  = 2000;

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

	/** Keys that make up a chart's look, copied by "Copy the style of another chart". */
	const STYLE_KEYS = array( 'bar_color', 'range_color', 'track_color', 'label_color', 'value_color', 'axis_color', 'font_family', 'bar_size', 'chart_height', 'radius', 'patterns', 'animate', 'animation', 'easing', 'duration', 'stagger', 'count_up' );

	/**
	 * Names and field labels of the chart types, shared by every editor.
	 *
	 * 'second' is the label of the second value; empty when the type has none.
	 *
	 * @return array<string, array{name: string, value: string, second: string, help: string}>
	 */
	public static function type_labels(): array {
		return array(
			'bars'     => array(
				'name'   => __( 'Bars with ranges', 'qaiyo-easy-charts' ),
				'value'  => __( 'Value', 'qaiyo-easy-charts' ),
				'second' => __( 'Range end', 'qaiyo-easy-charts' ),
				'help'   => __( 'Value is where the solid bar ends. Range end is optional: it adds a highlighted range after the value (e.g. 10–15). Value text replaces the automatic text, e.g. "up to 5".', 'qaiyo-easy-charts' ),
			),
			'compare'  => array(
				'name'   => __( 'Comparison bars', 'qaiyo-easy-charts' ),
				'value'  => __( 'First series', 'qaiyo-easy-charts' ),
				'second' => __( 'Second series', 'qaiyo-easy-charts' ),
				'help'   => __( 'Each row compares two values, e.g. your price and the market average. Name the two series under Values.', 'qaiyo-easy-charts' ),
			),
			'ring'     => array(
				'name'   => __( 'KPI rings', 'qaiyo-easy-charts' ),
				'value'  => __( 'Value', 'qaiyo-easy-charts' ),
				'second' => __( 'Goal', 'qaiyo-easy-charts' ),
				'help'   => __( 'Each row is a ring filled to value / goal. Without a goal the scale maximum or 100 is used, so percentages need no goal.', 'qaiyo-easy-charts' ),
			),
			'timeline' => array(
				'name'   => __( 'Timeline', 'qaiyo-easy-charts' ),
				'value'  => __( 'Start', 'qaiyo-easy-charts' ),
				'second' => __( 'Finish', 'qaiyo-easy-charts' ),
				'help'   => __( 'Each row is a bar from start to finish on the axis, e.g. in weeks. Leave the finish empty for a milestone.', 'qaiyo-easy-charts' ),
			),
			'bullet'   => array(
				'name'   => __( 'Bullet chart', 'qaiyo-easy-charts' ),
				'value'  => __( 'Value', 'qaiyo-easy-charts' ),
				'second' => __( 'Target', 'qaiyo-easy-charts' ),
				'help'   => __( 'The bar shows the value, the line the target. Bands (under Values) shade qualitative ranges, e.g. 50, 75, 100.', 'qaiyo-easy-charts' ),
			),
			'stacked'  => array(
				'name'   => __( '100% stacked bar', 'qaiyo-easy-charts' ),
				'value'  => __( 'Value', 'qaiyo-easy-charts' ),
				'second' => '',
				'help'   => __( 'One bar split into parts; the shares are calculated from the values.', 'qaiyo-easy-charts' ),
			),
			'rating'   => array(
				'name'   => __( 'Rating distribution', 'qaiyo-easy-charts' ),
				'value'  => __( 'Count', 'qaiyo-easy-charts' ),
				'second' => '',
				'help'   => __( 'One row per rating level: the label is the level (5, 4, 3…), the value the number of ratings. The average is calculated.', 'qaiyo-easy-charts' ),
			),
		);
	}

	/**
	 * The style part of a configuration (see STYLE_KEYS).
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @return array<string, mixed>
	 */
	public static function style_of( array $config ): array {
		return array_intersect_key( $config, array_flip( self::STYLE_KEYS ) );
	}

	/**
	 * Defaults, seeded from the global settings.
	 *
	 * @return array<mixed>
	 */
	public static function defaults(): array {
		$settings = Settings::get();

		$defaults = array(
			'type'          => 'bars',
			'chart_id'      => 0,
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
			'animation'     => 'grow',
			'duration'      => 0,
			'stagger'       => 0,
			'easing'        => 'smooth',
			'replay'        => false,
			'count_up'      => true,
			'lazy'          => $settings['lazy'],
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
			'series_a'      => '',
			'series_b'      => '',
			'bands'         => array(),
			'patterns'      => false,
			'tooltips'      => false,
			'export'        => false,
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

		$type = Cast::choice( $raw['type'], self::TYPES, 'bars' );

		$config = array(
			'type'          => $type,
			'chart_id'      => max( 0, Cast::int( $raw['chart_id'] ) ),
			// Only bars can stand up as columns; every other type has one fixed layout.
			'orientation'   => 'bars' === $type ? Cast::choice( $raw['orientation'], self::ORIENTATIONS, 'horizontal' ) : 'horizontal',
			'items'         => self::sanitize_items( Cast::arr( $raw['items'] ), $type ),
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
			'animation'     => Cast::choice( $raw['animation'], self::ANIMATIONS, 'grow' ),
			'duration'      => self::milliseconds( $raw['duration'], self::MAX_DURATION ),
			'stagger'       => self::milliseconds( $raw['stagger'], self::MAX_STAGGER ),
			'easing'        => Cast::choice( $raw['easing'], self::EASINGS, 'smooth' ),
			'replay'        => Cast::bool( $raw['replay'] ),
			'count_up'      => Cast::bool( $raw['count_up'] ),
			'lazy'          => Cast::bool( $raw['lazy'] ),
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
			'series_a'      => self::text( $raw['series_a'], 60 ),
			'series_b'      => self::text( $raw['series_b'], 60 ),
			'bands'         => self::bands( $raw['bands'] ),
			'patterns'      => Cast::bool( $raw['patterns'] ),
			'tooltips'      => Cast::bool( $raw['tooltips'] ),
			'export'        => Cast::bool( $raw['export'] ),
		);

		return $config;
	}

	/**
	 * Sanitize items.
	 *
	 * @param array<mixed> $items Raw rows.
	 * @param string       $type  Chart type: decides whether the second value must exceed the first.
	 * @return list<QecItem>
	 */
	public static function sanitize_items( array $items, string $type = 'bars' ): array {
		// A range end and a finish must lie after the start; a second series or a target may be anything.
		$ordered = in_array( $type, array( 'bars', 'timeline' ), true );

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
				'max'         => ( null !== $max && ( ! $ordered || ( null !== $value && $max > $value ) ) ) ? $max : '',
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
	private static function text( mixed $value, int $max_length, string $fallback = '' ): string {
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
	private static function number_or_empty( mixed $value ): float|string {
		$number = Cast::number( $value );
		return null === $number ? '' : $number;
	}

	/**
	 * Bullet chart bands: ascending thresholds from "60, 80", "60;80" or an array.
	 *
	 * @param mixed $value Any value.
	 * @return list<float>
	 */
	private static function bands( mixed $value ): array {
		if ( is_string( $value ) ) {
			// Thresholds are separated by commas, semicolons or spaces; decimals use a dot.
			$parts = preg_split( '/[\s,;|]+/', trim( $value ), -1, PREG_SPLIT_NO_EMPTY );
			$value = false === $parts ? array() : $parts;
		}

		$bands = array();
		foreach ( Cast::arr( $value ) as $band ) {
			$number = Cast::number( $band );
			if ( null !== $number ) {
				$bands[] = $number;
			}
		}
		$bands = array_values( array_unique( $bands, SORT_REGULAR ) );
		sort( $bands );
		return array_slice( $bands, 0, self::MAX_BANDS );
	}

	/**
	 * A whole number of milliseconds within 0..$max (0 = use the stylesheet default).
	 *
	 * @param mixed $value Any value, e.g. 800 or "800ms".
	 * @param int   $max   Upper bound.
	 */
	private static function milliseconds( mixed $value, int $max ): int {
		$number = Cast::number( is_string( $value ) ? preg_replace( '/ms$/i', '', trim( $value ) ) : $value );
		return null === $number ? 0 : (int) max( 0, min( $max, round( $number ) ) );
	}

	/**
	 * A number, or the default.
	 *
	 * @param mixed $value Any value.
	 * @param float $fallback Value used when $value is not a number.
	 */
	private static function number_or_default( mixed $value, float $fallback ): float {
		$number = Cast::number( $value );
		return null === $number ? $fallback : $number;
	}
}
