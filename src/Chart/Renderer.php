<?php
/**
 * Bar chart markup: horizontal bars or vertical columns, each with a solid base
 * value and an optional highlighted range on top (e.g. "10–15 days").
 *
 * One markup serves both orientations; CSS decides the direction, so the "auto"
 * orientation can switch from columns to bars on narrow containers without JS.
 *
 * Accessibility / SEO / AI: the chart is a <figure>. Unless disabled, the data
 * is also emitted as a real, visually hidden <table>, which screen readers,
 * search engines and AI crawlers read instead of the decorative graphic.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Chart;

use Qaiyo\EasyCharts\Frontend\Assets;
use Qaiyo\EasyCharts\Schema;
use Qaiyo\EasyCharts\Support\Cast;
use Qaiyo\EasyCharts\Support\Css;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the chart markup.
 *
 * @phpstan-import-type QecConfig from ChartConfig
 * @phpstan-import-type QecItem from ChartConfig
 */
final class Renderer {

	const MAX_TICKS = 50;

	/**
	 * Render a chart.
	 *
	 * @param array                $config     Sanitized configuration (ChartConfig::sanitize()).
	 * @phpstan-param QecConfig $config
	 * @param array<string,string> $root_attrs Extra root attributes: 'id', 'class', 'style'.
	 * @param bool                 $enqueue    Whether to enqueue the front-end assets.
	 */
	public static function render( array $config, array $root_attrs = array(), bool $enqueue = true ): string {
		$config = self::filter_config( $config );

		if ( $enqueue ) {
			Assets::enqueue();
		}

		$html = self::build( $config, $root_attrs );

		/**
		 * Filters the final chart HTML.
		 *
		 * @since 1.0.0
		 *
		 * @param string              $html   Chart markup.
		 * @param QecConfig $config Chart configuration.
		 */
		$filtered_html = apply_filters( 'qaiyo_easy_charts_chart_html', $html, $config );

		// Callbacks are third-party code: narrow the result instead of trusting it.
		return Cast::string( $filtered_html );
	}

	/**
	 * Apply the public configuration filter (and re-validate its result).
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @return QecConfig
	 */
	public static function filter_config( array $config ): array {
		/**
		 * Filters the sanitized configuration right before a chart is rendered.
		 *
		 * @since 1.0.0
		 *
		 * @param QecConfig $config Chart configuration.
		 */
		$filtered = apply_filters( 'qaiyo_easy_charts_config', $config );

		// Callbacks are third-party code: validate whatever comes back.
		return ChartConfig::sanitize( Cast::arr( $filtered ) );
	}

	/**
	 * Root classes, also used by builders that print their own root element.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @return string[]
	 */
	public static function root_classes( array $config ): array {
		return array_values(
			array_filter(
				array(
					'qec-chart',
					'qec-bars',
					'qec-orient-' . $config['orientation'],
					'qec-layout-' . $config['layout'],
					$config['hover_focus'] ? 'qec-hover-focus' : '',
					$config['show_values'] ? '' : 'qec-no-values',
					$config['show_axis'] ? '' : 'qec-no-axis',
					$config['animate'] ? 'qec-anim-' . $config['animation'] : '',
					$config['animate'] ? 'qec-ease-' . $config['easing'] : '',
				)
			)
		);
	}

	/**
	 * Inline CSS variables from the per-chart style settings.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 */
	public static function root_style( array $config ): string {
		$vars = array();
		foreach ( ChartConfig::STYLE_VARS as $key => $var ) {
			$vars[ $var ] = (string) $config[ $key ];
		}
		$vars['--qec-duration'] = $config['duration'] > 0 ? $config['duration'] . 'ms' : '';
		$vars['--qec-stagger']  = $config['stagger'] > 0 ? $config['stagger'] . 'ms' : '';
		return Css::inline( $vars );
	}

	/**
	 * Data attributes that switch on the front-end behaviour (animation,
	 * replay, count-up, lazy loading), also used by builders that print
	 * their own root element.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @return array<string,string>
	 */
	public static function root_data( array $config ): array {
		$data = array();
		if ( $config['animate'] ) {
			$data['data-qec-animate'] = '1';
			if ( $config['replay'] ) {
				$data['data-qec-replay'] = '1';
			}
			if ( $config['count_up'] && $config['show_values'] ) {
				$data['data-qec-count'] = '1';
			}
		}
		if ( $config['lazy'] ) {
			$data['data-qec-lazy'] = '1';
		}
		return $data;
	}

	/**
	 * Everything inside the root element.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 */
	public static function inner( array $config ): string {
		$scale = self::scale( $config );
		$table = $config['data_table'] && array() !== $config['items'];

		$visual = '';

		// Columns show the unit as an axis title instead of on the top tick.
		if ( $config['show_axis'] && '' !== $config['unit'] && 'none' !== $config['unit_position'] && 'horizontal' !== $config['orientation'] ) {
			$visual .= '<div class="qec-axis-title" aria-hidden="true">' . esc_html( $config['unit'] ) . '</div>';
		}

		$visual .= $table
			? '<div class="qec-grid" aria-hidden="true">'
			: '<div class="qec-grid" role="list">';

		foreach ( $config['items'] as $index => $item ) {
			$visual .= self::row( $config, $scale, $item, (int) $index, $table );
		}

		if ( $config['show_axis'] ) {
			$visual .= self::axis( $config, $scale );
		}
		$visual .= '</div>';

		$html = self::caption( $config ) . ( $config['lazy'] ? self::lazy( $config, $visual ) : $visual );

		// The data table is never deferred: screen readers, search engines and AI read it at once.
		if ( $table ) {
			$html .= self::data_table( $config );
		}

		return $html;
	}

	/**
	 * Plain-text version of a chart (search indexes, feeds, AI extraction).
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 */
	public static function plain_text( array $config ): string {
		$lines = array();
		if ( '' !== $config['title'] ) {
			$lines[] = $config['title'];
		}
		if ( '' !== $config['description'] ) {
			$lines[] = $config['description'];
		}
		foreach ( $config['items'] as $item ) {
			$lines[] = $item['label'] . ': ' . self::table_value( $config, $item );
		}
		return implode( "\n", $lines );
	}

	/**
	 * Full chart markup including the root element.
	 *
	 * @param array                $config     Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array<string,string> $root_attrs Extra root attributes.
	 */
	private static function build( array $config, array $root_attrs ): string {
		$classes = self::root_classes( $config );
		if ( ! empty( $root_attrs['class'] ) ) {
			$extra   = preg_split( '/\s+/', $root_attrs['class'] );
			$classes = array_merge( $classes, false === $extra ? array() : $extra );
		}

		$style = self::root_style( $config ) . ( $root_attrs['style'] ?? '' );

		$attributes = sprintf( ' class="%s"', esc_attr( implode( ' ', array_unique( array_map( 'sanitize_html_class', $classes ) ) ) ) );
		if ( ! empty( $root_attrs['id'] ) ) {
			$attributes .= sprintf( ' id="%s"', esc_attr( $root_attrs['id'] ) );
		}
		if ( '' !== $style ) {
			$attributes .= sprintf( ' style="%s"', esc_attr( $style ) );
		}
		foreach ( self::root_data( $config ) as $name => $value ) {
			$attributes .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
		}
		if ( ! $config['show_caption'] && '' !== $config['title'] ) {
			$attributes .= sprintf( ' aria-label="%s"', esc_attr( $config['title'] ) );
		}

		return '<figure' . $attributes . '>' . self::inner( $config ) . '</figure>';
	}

	/**
	 * Lazy loading: the drawn chart waits in a <template> and is mounted by
	 * chart.js when it scrolls near the viewport. A placeholder of about the
	 * same height keeps the layout stable; <noscript> keeps it visible without JS.
	 *
	 * @param array  $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param string $visual Escaped chart markup.
	 */
	private static function lazy( array $config, string $visual ): string {
		return sprintf(
			'<div class="qec-lazy-placeholder" style="%1$s" aria-hidden="true"></div><template class="qec-lazy-template">%2$s</template><noscript>%2$s</noscript>',
			esc_attr( '--qec-rows:' . count( $config['items'] ) ),
			$visual
		);
	}

	/**
	 * Visible figure caption.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 */
	private static function caption( array $config ): string {
		if ( ! $config['show_caption'] || ( '' === $config['title'] && '' === $config['description'] ) ) {
			return '';
		}

		$html = '<figcaption class="qec-caption">';
		if ( '' !== $config['title'] ) {
			$html .= '<span class="qec-title">' . esc_html( $config['title'] ) . '</span>';
		}
		if ( '' !== $config['description'] ) {
			$html .= '<span class="qec-description">' . esc_html( $config['description'] ) . '</span>';
		}
		return $html . '</figcaption>';
	}

	/**
	 * Markup of one bar.
	 *
	 * @param array                                      $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array{min: float, max: float, step: float} $scale  Scale.
	 * @param array                                      $item   Row.
	 * @phpstan-param QecItem $item
	 * @param int                                        $index Index.
	 * @param bool                                       $has_table Has table.
	 */
	private static function row( array $config, array $scale, array $item, int $index, bool $has_table ): string {
		$base_end = self::percent( $scale, (float) $item['value'] );

		$row_style = Css::inline(
			array(
				'--qec-i'         => (string) $index,
				'--qec-primary'   => $item['color'],
				'--qec-secondary' => $item['range_color'],
			)
		);

		$segments = sprintf(
			'<span class="qec-seg qec-seg--primary" style="%s"></span>',
			esc_attr( '--qec-start:0%;--qec-size:' . Css::percent( $base_end ) )
		);

		if ( '' !== $item['max'] ) {
			$range_end = self::percent( $scale, (float) $item['max'] );
			$segments .= sprintf(
				'<span class="qec-seg qec-seg--secondary" style="%s"></span>',
				esc_attr( '--qec-start:' . Css::percent( $base_end ) . ';--qec-size:' . Css::percent( max( 0.0, $range_end - $base_end ) ) )
			);
		}

		$value = self::value_text( $config, $item );

		$html  = '<div class="qec-row"' . ( $has_table ? '' : ' role="listitem"' ) . ' style="' . esc_attr( $row_style ) . '">';
		$html .= '<span class="qec-label">' . esc_html( $item['label'] ) . '</span>';
		$html .= '<span class="qec-track"' . ( $has_table ? '' : ' aria-hidden="true"' ) . '>' . $segments . '</span>';

		if ( $config['show_values'] ) {
			$html .= '<span class="qec-value">' . esc_html( $value ) . '</span>';
		} elseif ( ! $has_table ) {
			$html .= '<span class="qec-sr-only">' . esc_html( self::table_value( $config, $item ) ) . '</span>';
		}

		return $html . '</div>';
	}

	/**
	 * Markup of the value axis.
	 *
	 * @param array                                      $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array{min: float, max: float, step: float} $scale  Scale.
	 */
	private static function axis( array $config, array $scale ): string {
		$ticks = self::ticks( $scale );
		$last  = count( $ticks ) - 1;

		$html = '';
		foreach ( $ticks as $index => $tick ) {
			$text = esc_html( self::format_number( $tick ) );
			if ( '' !== $config['unit'] && ( 'all' === $config['unit_position'] || ( 'last' === $config['unit_position'] && $index === $last ) ) ) {
				$text .= '<span class="qec-unit"> ' . esc_html( $config['unit'] ) . '</span>';
			}

			$html .= sprintf(
				'<span class="qec-tick" style="%s">%s</span>',
				esc_attr( '--qec-pos:' . Css::percent( self::percent( $scale, $tick ) ) ),
				$text
			);
		}

		return '<div class="qec-axis-row" aria-hidden="true">'
			. '<span class="qec-axis-spacer"></span>'
			. '<div class="qec-axis">' . $html . '</div>'
			. '<span class="qec-axis-spacer"></span>'
			. '</div>';
	}

	/**
	 * Visually hidden data table for screen readers, search and AI.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 */
	private static function data_table( array $config ): string {
		$caption = '' !== $config['title'] ? $config['title'] : __( 'Chart data', 'qaiyo-easy-charts' );

		$html  = '<table class="qec-data-table qec-sr-only">';
		$html .= '<caption>' . esc_html( $caption ) . '</caption>';
		$html .= '<thead><tr><th scope="col">' . esc_html__( 'Item', 'qaiyo-easy-charts' ) . '</th><th scope="col">' . esc_html__( 'Value', 'qaiyo-easy-charts' ) . '</th></tr></thead><tbody>';

		foreach ( $config['items'] as $item ) {
			$html .= '<tr><th scope="row">' . esc_html( $item['label'] ) . '</th><td>' . esc_html( self::table_value( $config, $item ) ) . '</td></tr>';
		}

		return $html . '</tbody></table>';
	}

	/**
	 * Value shown next to / above the bar.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array $item   Row.
	 * @phpstan-param QecItem $item
	 */
	private static function value_text( array $config, array $item ): string {
		if ( '' !== $item['text'] ) {
			return $item['text'];
		}

		$text = self::format_number( (float) $item['value'] );
		if ( '' !== $item['max'] ) {
			$text .= $config['separator'] . self::format_number( (float) $item['max'] );
		}

		return $config['prefix'] . $text . $config['suffix'];
	}

	/**
	 * Value for the data table / plain text: always with the unit.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array $item   Row.
	 * @phpstan-param QecItem $item
	 */
	private static function table_value( array $config, array $item ): string {
		$text = self::value_text( $config, $item );
		return '' !== $config['unit'] ? $text . ' ' . $config['unit'] : $text;
	}

	/**
	 * Resolved axis range and step.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @return array{min: float, max: float, step: float}
	 */
	public static function scale( array $config ): array {
		$min  = (float) $config['scale_min'];
		$step = '' === $config['step'] ? 0.0 : (float) $config['step'];
		$max  = '' === $config['scale_max'] ? null : (float) $config['scale_max'];

		if ( null === $max || $max <= $min ) {
			$max = $min;
			foreach ( $config['items'] as $item ) {
				$max = max( $max, (float) ( '' === $item['max'] ? $item['value'] : $item['max'] ) );
			}
			if ( $step > 0 ) {
				$max = $min + ceil( ( $max - $min ) / $step ) * $step;
			}
			if ( $max <= $min ) {
				$max = $min + ( $step > 0 ? $step : 1 );
			}
		}

		if ( $step <= 0 ) {
			$step = ( $max - $min ) / 4;
		}

		return array(
			'min'  => $min,
			'max'  => $max,
			'step' => $step,
		);
	}

	/**
	 * Axis tick values.
	 *
	 * @param array{min: float, max: float, step: float} $scale Scale.
	 * @return float[]
	 */
	private static function ticks( array $scale ): array {
		if ( ( $scale['max'] - $scale['min'] ) / $scale['step'] > self::MAX_TICKS ) {
			return array( $scale['min'], $scale['max'] );
		}

		$ticks   = array();
		$epsilon = $scale['step'] / 1000;
		for ( $i = 0; $scale['min'] + $i * $scale['step'] <= $scale['max'] + $epsilon; $i++ ) {
			$ticks[] = round( $scale['min'] + $i * $scale['step'], 6 );
		}
		return $ticks;
	}

	/**
	 * Position of a value on the scale, in percent (0–100).
	 *
	 * @param array{min: float, max: float, step: float} $scale Scale.
	 * @param float                                      $value Value to place.
	 */
	private static function percent( array $scale, float $value ): float {
		$percent = ( $value - $scale['min'] ) / ( $scale['max'] - $scale['min'] ) * 100;
		return min( 100.0, max( 0.0, $percent ) );
	}

	/**
	 * Number in the site locale, without trailing zeros.
	 *
	 * @param float $number Number.
	 */
	public static function format_number( float $number ): string {
		if ( floor( $number ) === $number ) {
			return number_format_i18n( $number );
		}

		global $wp_locale;
		$decimal_point = $wp_locale instanceof \WP_Locale ? Cast::string( $wp_locale->number_format['decimal_point'] ?? '.' ) : '.';
		$decimal_point = '' !== $decimal_point ? $decimal_point : '.';

		return rtrim( rtrim( number_format_i18n( $number, 2 ), '0' ), $decimal_point );
	}
}
