<?php
/**
 * Row charts on a shared value axis: bars with ranges, comparison bars,
 * timelines and bullet charts.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Chart\Types;

use Qaiyo\EasyCharts\Chart\Renderer;
use Qaiyo\EasyCharts\Support\Css;

defined( 'ABSPATH' ) || exit;

/**
 * One row per item: label · track · value, and a value axis below. One markup
 * serves both bar orientations; CSS decides the direction, so the "auto"
 * orientation can switch from columns to bars on narrow containers without JS.
 *
 * - bars:     a solid value and an optional highlighted range on top ("10–15").
 * - compare:  two series per row, one above the other.
 * - timeline: a bar from start to finish; a single date is a milestone.
 * - bullet:   the value against a target marker over qualitative bands.
 *
 * @phpstan-import-type QecConfig from \Qaiyo\EasyCharts\Chart\ChartConfig
 * @phpstan-import-type QecItem from \Qaiyo\EasyCharts\Chart\ChartConfig
 * @phpstan-import-type QecTable from \Qaiyo\EasyCharts\Chart\ChartConfig
 */
final class RowChart implements ChartType {

	/**
	 * Escaped markup of the drawn chart.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array $tips   One sentence per item.
	 * @phpstan-param list<string> $tips
	 */
	public static function visual( array $config, array $tips ): string {
		$scale = Renderer::scale( $config );
		$html  = '';

		// Columns show the unit as an axis title instead of on the top tick.
		if ( $config['show_axis'] && '' !== $config['unit'] && 'none' !== $config['unit_position'] && 'horizontal' !== $config['orientation'] ) {
			$html .= '<div class="qec-axis-title" aria-hidden="true">' . esc_html( $config['unit'] ) . '</div>';
		}

		if ( 'compare' === $config['type'] ) {
			$html .= self::legend( $config );
		}

		$html .= '<div class="qec-grid"' . Renderer::list_attrs( $config ) . '>';
		foreach ( $config['items'] as $index => $item ) {
			$html .= self::row( $config, $scale, $item, $index, $tips[ $index ] ?? '' );
		}
		if ( $config['show_axis'] ) {
			$html .= self::axis( $config, $scale );
		}

		return $html . '</div>';
	}

	/**
	 * The chart's data as table cells.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @return QecTable
	 */
	public static function table( array $config ): array {
		$item_heading = __( 'Item', 'qaiyo-easy-charts' );

		$head = match ( $config['type'] ) {
			'compare'  => array( $item_heading, self::series_name( $config, 'a' ), self::series_name( $config, 'b' ) ),
			'timeline' => array( $item_heading, __( 'Start', 'qaiyo-easy-charts' ), __( 'Finish', 'qaiyo-easy-charts' ) ),
			'bullet'   => array( $item_heading, __( 'Value', 'qaiyo-easy-charts' ), __( 'Target', 'qaiyo-easy-charts' ) ),
			default    => array( $item_heading, __( 'Value', 'qaiyo-easy-charts' ) ),
		};

		$rows = array();
		foreach ( $config['items'] as $item ) {
			if ( 'bars' === $config['type'] ) {
				$rows[] = array( $item['label'], Renderer::with_unit( $config, Renderer::value_text( $config, $item ) ) );
				continue;
			}

			$second = '' === $item['max'] ? '–' : Renderer::with_unit( $config, Renderer::number_text( $config, (float) $item['max'] ) );
			$first  = 'timeline' === $config['type'] || '' === $item['text']
				? Renderer::with_unit( $config, Renderer::number_text( $config, (float) $item['value'] ) )
				: $item['text'];
			$rows[] = array( $item['label'], $first, $second );
		}

		return array(
			'head' => $head,
			'rows' => $rows,
			'note' => '',
		);
	}

	/**
	 * Name of a compared series, with a numbered fallback.
	 *
	 * @param array  $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param string $series 'a' or 'b'.
	 */
	private static function series_name( array $config, string $series ): string {
		$name = 'a' === $series ? $config['series_a'] : $config['series_b'];
		if ( '' !== $name ) {
			return $name;
		}
		/* translators: %d: series number (1 or 2). */
		return sprintf( __( 'Series %d', 'qaiyo-easy-charts' ), 'a' === $series ? 1 : 2 );
	}

	/**
	 * Legend of the two compared series.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 */
	private static function legend( array $config ): string {
		return '<div class="qec-legend" aria-hidden="true">'
			. '<span class="qec-legend-item"><span class="qec-swatch qec-swatch--a"></span>' . esc_html( self::series_name( $config, 'a' ) ) . '</span>'
			. '<span class="qec-legend-item"><span class="qec-swatch qec-swatch--b"></span>' . esc_html( self::series_name( $config, 'b' ) ) . '</span>'
			. '</div>';
	}

	/**
	 * Markup of one row.
	 *
	 * @param array                                      $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array{min: float, max: float, step: float} $scale  Scale.
	 * @param array                                      $item   Row.
	 * @phpstan-param QecItem $item
	 * @param int                                        $index  Position.
	 * @param string                                     $tip    The row as one sentence.
	 */
	private static function row( array $config, array $scale, array $item, int $index, string $tip ): string {
		$hidden = Renderer::hidden( $config );

		$html  = Renderer::mark_open(
			$config,
			'div',
			'qec-row',
			array(
				'--qec-i'         => (string) $index,
				'--qec-primary'   => $item['color'],
				'--qec-secondary' => $item['range_color'],
			)
		);
		$html .= '<span class="qec-label"' . $hidden . '>' . esc_html( $item['label'] ) . '</span>';
		$html .= '<span class="qec-track" aria-hidden="true">' . self::marks( $config, $scale, $item ) . '</span>';

		if ( $config['show_values'] ) {
			$html .= self::value( $config, $item, $hidden );
		}

		return $html . Renderer::mark_close( $config, 'div', $tip );
	}

	/**
	 * The drawn part of a row, per chart type.
	 *
	 * @param array                                      $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array{min: float, max: float, step: float} $scale  Scale.
	 * @param array                                      $item   Row.
	 * @phpstan-param QecItem $item
	 */
	private static function marks( array $config, array $scale, array $item ): string {
		$value  = Renderer::percent( $scale, (float) $item['value'] );
		$second = '' === $item['max'] ? null : Renderer::percent( $scale, (float) $item['max'] );

		switch ( $config['type'] ) {
			case 'compare':
				$html = self::segment( 'qec-seg--primary qec-seg--a', 0.0, $value );
				if ( null !== $second ) {
					$html .= self::segment( 'qec-seg--secondary qec-seg--b', 0.0, $second );
				}
				return $html;

			case 'timeline':
				if ( null === $second ) {
					return '<span class="qec-seg qec-milestone" style="' . esc_attr( '--qec-pos:' . Css::percent( $value ) ) . '"></span>';
				}
				return self::segment( 'qec-seg--primary', $value, $second - $value );

			case 'bullet':
				$html  = self::bands( $config, $scale );
				$html .= self::segment( 'qec-seg--primary qec-seg--measure', 0.0, $value );
				if ( null !== $second ) {
					$html .= '<span class="qec-target" style="' . esc_attr( '--qec-pos:' . Css::percent( $second ) ) . '"></span>';
				}
				return $html;

			default:
				$html = self::segment( 'qec-seg--primary', 0.0, $value );
				if ( null !== $second ) {
					$html .= self::segment( 'qec-seg--secondary', $value, max( 0.0, $second - $value ) );
				}
				return $html;
		}
	}

	/**
	 * Qualitative bands of a bullet chart (darkest first, lighter outwards).
	 *
	 * @param array                                      $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array{min: float, max: float, step: float} $scale  Scale.
	 */
	private static function bands( array $config, array $scale ): string {
		$html  = '';
		$start = 0.0;
		$count = count( $config['bands'] );
		foreach ( $config['bands'] as $index => $band ) {
			$end   = Renderer::percent( $scale, $band );
			$html .= sprintf(
				'<span class="qec-band" style="%s"></span>',
				esc_attr( '--qec-start:' . Css::percent( $start ) . ';--qec-size:' . Css::percent( max( 0.0, $end - $start ) ) . ';--qec-band:' . ( $count - $index ) )
			);
			$start = $end;
		}
		return $html;
	}

	/**
	 * One segment of a track.
	 *
	 * @param string $classes Modifier classes.
	 * @param float  $start Start, in percent.
	 * @param float  $size  Length, in percent.
	 */
	private static function segment( string $classes, float $start, float $size ): string {
		return sprintf(
			'<span class="qec-seg %s" style="%s"></span>',
			esc_attr( $classes ),
			esc_attr( '--qec-start:' . Css::percent( $start ) . ';--qec-size:' . Css::percent( $size ) )
		);
	}

	/**
	 * The visible value of a row.
	 *
	 * @param array  $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array  $item   Row.
	 * @phpstan-param QecItem $item
	 * @param string $hidden aria-hidden attribute, if any.
	 */
	private static function value( array $config, array $item, string $hidden ): string {
		if ( 'compare' === $config['type'] && '' === $item['text'] ) {
			$html = '<span class="qec-count">' . esc_html( Renderer::number_text( $config, (float) $item['value'] ) ) . '</span>';
			if ( '' !== $item['max'] ) {
				$html .= '<span class="qec-count">' . esc_html( Renderer::number_text( $config, (float) $item['max'] ) ) . '</span>';
			}
			return '<span class="qec-value qec-value--pair"' . $hidden . '>' . $html . '</span>';
		}

		return '<span class="qec-value qec-count"' . $hidden . '>' . esc_html( Renderer::value_text( $config, $item ) ) . '</span>';
	}

	/**
	 * Markup of the value axis.
	 *
	 * @param array                                      $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array{min: float, max: float, step: float} $scale  Scale.
	 */
	private static function axis( array $config, array $scale ): string {
		$ticks = Renderer::ticks( $scale );
		$last  = count( $ticks ) - 1;

		$html = '';
		foreach ( $ticks as $index => $tick ) {
			$text = esc_html( Renderer::format_number( $tick ) );
			if ( '' !== $config['unit'] && ( 'all' === $config['unit_position'] || ( 'last' === $config['unit_position'] && $index === $last ) ) ) {
				$text .= '<span class="qec-unit"> ' . esc_html( $config['unit'] ) . '</span>';
			}

			$html .= sprintf(
				'<span class="qec-tick" style="%s">%s</span>',
				esc_attr( '--qec-pos:' . Css::percent( Renderer::percent( $scale, $tick ) ) ),
				$text
			);
		}

		return '<div class="qec-axis-row" aria-hidden="true">'
			. '<span class="qec-axis-spacer"></span>'
			. '<div class="qec-axis">' . $html . '</div>'
			. '<span class="qec-axis-spacer"></span>'
			. '</div>';
	}
}
