<?php
/**
 * Chart markup shared by every chart type: the root figure, caption, lazy
 * loading, export buttons, data table and the helpers the types draw with.
 * The drawing itself comes from the type classes in Chart\Types.
 *
 * Accessibility / SEO / AI: the chart is a <figure>. Unless disabled, the data
 * is also emitted as a real, visually hidden <table>, which screen readers,
 * search engines and AI crawlers read instead of the decorative graphic.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Chart;

use Qaiyo\EasyCharts\Chart\Types\ChartType;
use Qaiyo\EasyCharts\Chart\Types\RatingChart;
use Qaiyo\EasyCharts\Chart\Types\RingChart;
use Qaiyo\EasyCharts\Chart\Types\RowChart;
use Qaiyo\EasyCharts\Chart\Types\StackedChart;
use Qaiyo\EasyCharts\Frontend\Assets;
use Qaiyo\EasyCharts\Schema;
use Qaiyo\EasyCharts\Stats\Views;
use Qaiyo\EasyCharts\Support\Cast;
use Qaiyo\EasyCharts\Support\Css;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the chart markup.
 *
 * @phpstan-import-type QecConfig from ChartConfig
 * @phpstan-import-type QecItem from ChartConfig
 * @phpstan-import-type QecTable from ChartConfig
 */
final class Renderer {

	const MAX_TICKS = 50;

	/** The stylesheet's default timing (chart.css), in milliseconds. */
	const DEFAULT_DURATION = 1100;
	const DEFAULT_STAGGER  = 140;

	/**
	 * The whole entrance animation, including counting up, ends within this many
	 * milliseconds (WCAG 2.2.2: moving content under 5 seconds needs no pause button).
	 */
	const MAX_MOTION = 5000;

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
					'qec-type-' . $config['type'],
					'qec-scheme-' . $config['scheme'],
					in_array( $config['type'], ChartConfig::ROW_TYPES, true ) ? 'qec-bars' : '',
					$config['patterns'] ? 'qec-patterns' : '',
					$config['tooltips'] ? 'qec-tooltips' : '',
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
		$vars['--qec-stagger']  = self::stagger( $config );
		if ( 'ring' === $config['type'] ) {
			$vars['--qec-ring-size'] = $config['chart_height'];
		}
		// A background makes the chart a card with some padding; transparent means none.
		$vars['--qec-pad']      = '' !== $config['bg_color'] && 'transparent' !== $config['bg_color'] ? '24px' : '';
		$vars['--qec-dark-pad'] = 'transparent' === $config['dark_bg_color'] ? '0px' : '';
		return Css::inline( $vars );
	}

	/**
	 * Delay between items, shortened when needed so that the last item has
	 * finished (bars and counting) within MAX_MOTION. '' = the stylesheet default.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 */
	public static function stagger( array $config ): string {
		$duration = $config['duration'] > 0 ? $config['duration'] : self::DEFAULT_DURATION;
		$stagger  = $config['stagger'] > 0 ? $config['stagger'] : self::DEFAULT_STAGGER;
		$gaps     = count( $config['items'] ) - 1;

		// The value of the last item finishes counting at stagger * gaps + 1.4 * duration.
		if ( $gaps > 0 ) {
			$stagger = (int) max( 0, min( $stagger, floor( ( self::MAX_MOTION - 1.4 * $duration ) / $gaps ) ) );
		}

		return $config['stagger'] > 0 || self::DEFAULT_STAGGER !== $stagger ? $stagger . 'ms' : '';
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
		if ( Views::should_track( $config['chart_id'] ) ) {
			$data['data-qec-view'] = (string) $config['chart_id'];
		}
		// Lets dark mode styles of the theme or other plugins leave the chart alone.
		if ( 'light' !== $config['scheme'] ) {
			$data['data-qec-scheme'] = $config['scheme'];
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
		$type  = self::type_class( $config['type'] );
		$table = $type::table( $config );

		$visual = $type::visual( $config, self::tips( $table ) );
		$html   = self::caption( $config ) . ( $config['lazy'] ? self::lazy( $config, $visual ) : $visual );

		if ( $config['export'] && array() !== $config['items'] ) {
			$html .= self::export_buttons();
		}

		// The data table is never deferred: screen readers, search engines and AI read it at once.
		if ( self::has_table( $config ) ) {
			$html .= self::data_table( $config, $table );
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

		$table = self::type_class( $config['type'] )::table( $config );
		$lines = array_merge( $lines, self::tips( $table ) );
		if ( '' !== $table['note'] ) {
			$lines[] = $table['note'];
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
	 * Download buttons for the PNG / SVG export (chart.js does the work; hidden without JS).
	 */
	private static function export_buttons(): string {
		return '<div class="qec-export">'
			. '<button type="button" class="qec-export-btn" data-qec-export="png" aria-label="' . esc_attr__( 'Download the chart as PNG', 'qaiyo-easy-charts' ) . '">PNG</button>'
			. '<button type="button" class="qec-export-btn" data-qec-export="svg" aria-label="' . esc_attr__( 'Download the chart as SVG', 'qaiyo-easy-charts' ) . '">SVG</button>'
			. '</div>';
	}

	/**
	 * Visually hidden data table for screen readers, search and AI.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array $table  Table contents.
	 * @phpstan-param QecTable $table
	 */
	private static function data_table( array $config, array $table ): string {
		$caption = '' !== $config['title'] ? $config['title'] : __( 'Chart data', 'qaiyo-easy-charts' );
		if ( '' !== $table['note'] ) {
			$caption .= ' — ' . $table['note'];
		}

		// Hidden through a wrapper: tables ignore overflow, so a hidden table could still widen the page.
		$html  = '<div class="qec-sr-only"><table class="qec-data-table">';
		$html .= '<caption>' . esc_html( $caption ) . '</caption><thead><tr>';
		foreach ( $table['head'] as $heading ) {
			$html .= '<th scope="col">' . esc_html( $heading ) . '</th>';
		}
		$html .= '</tr></thead><tbody>';

		foreach ( $table['rows'] as $row ) {
			$html .= '<tr><th scope="row">' . esc_html( (string) array_shift( $row ) ) . '</th>';
			foreach ( $row as $cell ) {
				$html .= '<td>' . esc_html( $cell ) . '</td>';
			}
			$html .= '</tr>';
		}

		return $html . '</tbody></table></div>';
	}

	/**
	 * One sentence per item ("Label: value"), used for tooltips, screen-reader
	 * text and plain text.
	 *
	 * @param array $table Table contents.
	 * @phpstan-param QecTable $table
	 * @return list<string>
	 */
	private static function tips( array $table ): array {
		$columns = array_slice( $table['head'], 1 );
		$tips    = array();
		foreach ( $table['rows'] as $row ) {
			$label = (string) array_shift( $row );
			$cells = array();
			foreach ( $row as $index => $cell ) {
				// With one value column the heading is obvious; with more, name each value.
				$cells[] = count( $columns ) > 1 && isset( $columns[ $index ] ) ? $columns[ $index ] . ' ' . $cell : $cell;
			}
			$tips[] = $label . ': ' . implode( ', ', $cells );
		}
		return $tips;
	}

	/**
	 * The class that draws a chart type.
	 *
	 * @param string $type Chart type.
	 * @return class-string<ChartType>
	 */
	private static function type_class( string $type ): string {
		return match ( $type ) {
			'ring'    => RingChart::class,
			'stacked' => StackedChart::class,
			'rating'  => RatingChart::class,
			default   => RowChart::class,
		};
	}

	/**
	 * Whether the chart carries the hidden data table.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 */
	public static function has_table( array $config ): bool {
		return $config['data_table'] && array() !== $config['items'];
	}

	/**
	 * Accessibility of the drawn chart. With the data table (and no tooltips) the
	 * drawing is decorative and hidden from assistive technology; otherwise it is
	 * a list whose items carry their own text.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 */
	public static function is_list( array $config ): bool {
		return $config['tooltips'] || ! self::has_table( $config );
	}

	/**
	 * Attributes of the element that holds the marks.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 */
	public static function list_attrs( array $config ): string {
		return self::is_list( $config ) ? ' role="list"' : ' aria-hidden="true"';
	}

	/**
	 * Hides a visible part of a mark when the mark speaks through its own text.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 */
	public static function hidden( array $config ): string {
		return self::is_list( $config ) ? ' aria-hidden="true"' : '';
	}

	/**
	 * Opening tag of a mark: one item of the chart (a row, a ring, a legend entry).
	 *
	 * @param array                $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param string               $tag    Element name.
	 * @param string               $classes Extra classes.
	 * @param array<string,string> $vars   CSS custom properties.
	 */
	public static function mark_open( array $config, string $tag, string $classes, array $vars ): string {
		$html = '<' . $tag . ' class="' . esc_attr( trim( 'qec-mark ' . $classes ) ) . '"';
		$css  = Css::inline( $vars );
		if ( '' !== $css ) {
			$html .= ' style="' . esc_attr( $css ) . '"';
		}
		if ( self::is_list( $config ) ) {
			$html .= ' role="listitem"';
		}
		if ( $config['tooltips'] ) {
			$html .= ' tabindex="0"';
		}
		return $html . '>';
	}

	/**
	 * Closing part of a mark: its text for screen readers and the tooltip.
	 *
	 * @param array  $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param string $tag    Element name.
	 * @param string $tip    The item as one sentence.
	 */
	public static function mark_close( array $config, string $tag, string $tip ): string {
		$html = '';
		if ( self::is_list( $config ) ) {
			$html .= '<span class="qec-sr-only">' . esc_html( $tip ) . '</span>';
		}
		if ( $config['tooltips'] ) {
			$html .= '<span class="qec-tip" aria-hidden="true">' . esc_html( $tip ) . '</span>';
		}
		return $html . '</' . $tag . '>';
	}

	/**
	 * Value shown on the chart: the custom text, or the formatted number (with
	 * the range end for bars and timelines).
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array $item   Row.
	 * @phpstan-param QecItem $item
	 */
	public static function value_text( array $config, array $item ): string {
		if ( '' !== $item['text'] ) {
			return $item['text'];
		}

		$text = self::format_number( (float) $item['value'] );
		if ( '' !== $item['max'] && in_array( $config['type'], array( 'bars', 'timeline' ), true ) ) {
			$text .= $config['separator'] . self::format_number( (float) $item['max'] );
		}

		return $config['prefix'] . $text . $config['suffix'];
	}

	/**
	 * A number with the chart's prefix and suffix.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param float $number Number.
	 */
	public static function number_text( array $config, float $number ): string {
		return $config['prefix'] . self::format_number( $number ) . $config['suffix'];
	}

	/**
	 * Text with the unit appended (data table, tooltips, plain text).
	 *
	 * @param array  $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param string $text   Value text.
	 */
	public static function with_unit( array $config, string $text ): string {
		return '' !== $config['unit'] && '' !== $text ? $text . ' ' . $config['unit'] : $text;
	}

	/**
	 * A share as a percentage ("62%", "12.5%").
	 *
	 * @param float $part  Part.
	 * @param float $total Total.
	 */
	public static function share( float $part, float $total ): string {
		return self::format_number( $total > 0 ? round( $part / $total * 100, 1 ) : 0.0 ) . '%';
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
				$max = max( $max, (float) $item['value'], '' === $item['max'] ? $min : (float) $item['max'] );
			}
			foreach ( $config['bands'] as $band ) {
				$max = max( $max, $band );
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
	public static function ticks( array $scale ): array {
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
	public static function percent( array $scale, float $value ): float {
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
