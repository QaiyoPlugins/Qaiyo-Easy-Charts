<?php
/**
 * Range Bars chart: horizontal bars with a solid base value and an optional
 * highlighted range on top (e.g. "10–15 days"), plus a shared axis.
 *
 * Builder integrations translate their settings into the normalized args below
 * and use this class for all markup, so every builder renders identical HTML.
 *
 * @package QaiyoEasyCharts
 */

namespace Qaiyo\EasyCharts\Charts;

use Qaiyo\EasyCharts\Helpers;

defined( 'ABSPATH' ) || exit;

final class Range_Bars {

	const MAX_TICKS = 50;

	public static function defaults() {
		return array(
			'items'           => array(),
			'scale_min'       => 0,
			'scale_max'       => null, // null = automatic.
			'step'            => 5,
			'unit'            => '',
			'unit_position'   => 'last', // last | all | none.
			'show_axis'       => true,
			'show_values'     => true,
			'range_separator' => '–',
			'value_prefix'    => '',
			'value_suffix'    => '',
			'layout'          => 'auto', // auto | inline | stacked.
			'animate'         => true,
			'hover_focus'     => true,
		);
	}

	/**
	 * Default rows shown when the element is first dropped onto a page.
	 */
	public static function demo_items() {
		return array(
			array(
				'label'      => 'Landing page',
				'value'      => 5,
				'max'        => '',
				'value_text' => 'akár 5',
			),
			array(
				'label'      => 'Üzleti weboldal',
				'value'      => 10,
				'max'        => 15,
				'value_text' => '',
			),
			array(
				'label'      => 'Alap webshop',
				'value'      => 10,
				'max'        => 20,
				'value_text' => '',
			),
		);
	}

	/**
	 * @param array $args Raw args, see defaults().
	 * @return array
	 */
	public static function normalize( array $args ) {
		$args = array_merge( self::defaults(), $args );

		$items = array();
		foreach ( (array) $args['items'] as $item ) {
			$value = Helpers::to_number( isset( $item['value'] ) ? $item['value'] : null );
			if ( null === $value ) {
				continue;
			}
			$max = Helpers::to_number( isset( $item['max'] ) ? $item['max'] : null );

			$items[] = array(
				'label'           => isset( $item['label'] ) ? (string) $item['label'] : '',
				'value'           => $value,
				'max'             => ( null !== $max && $max > $value ) ? $max : null,
				'value_text'      => isset( $item['value_text'] ) ? trim( (string) $item['value_text'] ) : '',
				'primary_color'   => isset( $item['primary_color'] ) ? Helpers::color( $item['primary_color'] ) : '',
				'secondary_color' => isset( $item['secondary_color'] ) ? Helpers::color( $item['secondary_color'] ) : '',
			);
		}
		$args['items'] = $items;

		$scale_min = Helpers::to_number( $args['scale_min'] );
		$scale_min = null === $scale_min ? 0.0 : $scale_min;
		$step      = Helpers::to_number( $args['step'] );
		$step      = ( null === $step || $step <= 0 ) ? null : $step;
		$scale_max = Helpers::to_number( $args['scale_max'] );

		if ( null === $scale_max || $scale_max <= $scale_min ) {
			$scale_max = $scale_min;
			foreach ( $items as $item ) {
				$scale_max = max( $scale_max, null === $item['max'] ? $item['value'] : $item['max'] );
			}
			if ( $step ) {
				$scale_max = $scale_min + ceil( ( $scale_max - $scale_min ) / $step ) * $step;
			}
			if ( $scale_max <= $scale_min ) {
				$scale_max = $scale_min + ( $step ? $step : 1 );
			}
		}

		$args['scale_min'] = $scale_min;
		$args['scale_max'] = $scale_max;
		$args['step']      = $step ? $step : ( $scale_max - $scale_min ) / 4;

		if ( ! in_array( $args['unit_position'], array( 'last', 'all', 'none' ), true ) ) {
			$args['unit_position'] = 'last';
		}
		if ( ! in_array( $args['layout'], array( 'auto', 'inline', 'stacked' ), true ) ) {
			$args['layout'] = 'auto';
		}
		foreach ( array( 'show_axis', 'show_values', 'animate', 'hover_focus' ) as $flag ) {
			$args[ $flag ] = (bool) $args[ $flag ];
		}

		return $args;
	}

	/**
	 * Classes for the chart's root element.
	 */
	public static function root_classes( array $args ) {
		return array(
			'qec-chart',
			'qec-range-bars',
			'qec-layout-' . $args['layout'],
			$args['hover_focus'] ? 'qec-hover-focus' : '',
			$args['show_values'] ? '' : 'qec-no-values',
		);
	}

	/**
	 * Extra (non-class) attributes for the chart's root element.
	 */
	public static function root_attributes( array $args ) {
		return array(
			'data-qec-animate' => $args['animate'],
		);
	}

	/**
	 * Full chart markup including the root element.
	 */
	public static function render( array $args, array $extra_root_attributes = array() ) {
		$args = self::normalize( $args );

		$classes = self::root_classes( $args );
		if ( isset( $extra_root_attributes['class'] ) ) {
			$classes = array_merge( $classes, (array) $extra_root_attributes['class'] );
			unset( $extra_root_attributes['class'] );
		}

		$attributes = array_merge( array( 'class' => $classes ), self::root_attributes( $args ), $extra_root_attributes );

		return '<div' . Helpers::attributes( $attributes ) . '>' . self::render_inner( $args ) . '</div>';
	}

	/**
	 * Chart markup without the root element (for builders that render their own root).
	 *
	 * @param array $args Normalized args.
	 */
	public static function render_inner( array $args ) {
		$html = '<div class="qec-grid" role="list">';

		foreach ( $args['items'] as $index => $item ) {
			$html .= self::render_row( $args, $item, $index );
		}

		if ( $args['show_axis'] ) {
			$html .= self::render_axis( $args );
		}

		return $html . '</div>';
	}

	private static function render_row( array $args, array $item, $index ) {
		$row_style = Helpers::style(
			array(
				'--qec-i'         => (string) $index,
				'--qec-primary'   => $item['primary_color'],
				'--qec-secondary' => $item['secondary_color'],
			)
		);

		$base_end = self::percent( $args, $item['value'] );

		$segments = sprintf(
			'<span class="qec-seg qec-seg--primary" style="%s"></span>',
			esc_attr( Helpers::style( array( 'width' => self::css_percent( $base_end ) ) ) )
		);

		if ( null !== $item['max'] ) {
			$range_end = self::percent( $args, $item['max'] );
			$segments .= sprintf(
				'<span class="qec-seg qec-seg--secondary" style="%s"></span>',
				esc_attr(
					Helpers::style(
						array(
							'left'  => self::css_percent( $base_end ),
							'width' => self::css_percent( max( 0, $range_end - $base_end ) ),
						)
					)
				)
			);
		}

		$html  = '<div class="qec-row" role="listitem" style="' . esc_attr( $row_style ) . '">';
		$html .= '<span class="qec-label">' . esc_html( $item['label'] ) . '</span>';
		$html .= '<span class="qec-track" aria-hidden="true">' . $segments . '</span>';

		if ( $args['show_values'] ) {
			$html .= '<span class="qec-value">' . esc_html( self::value_text( $args, $item ) ) . '</span>';
		} else {
			$html .= '<span class="screen-reader-text qec-sr-only">' . esc_html( self::value_text( $args, $item ) ) . '</span>';
		}

		return $html . '</div>';
	}

	private static function render_axis( array $args ) {
		$ticks = self::ticks( $args );
		$last  = count( $ticks ) - 1;

		$html = '';
		foreach ( $ticks as $index => $tick ) {
			$text = Helpers::format_number( $tick );
			if ( '' !== $args['unit'] && ( 'all' === $args['unit_position'] || ( 'last' === $args['unit_position'] && $index === $last ) ) ) {
				$text .= ' ' . $args['unit'];
			}

			$html .= sprintf(
				'<span class="qec-tick" style="%s">%s</span>',
				esc_attr( '--qec-pos:' . self::css_percent( self::percent( $args, $tick ) ) ),
				esc_html( $text )
			);
		}

		return '<div class="qec-axis-row" aria-hidden="true">'
			. '<span class="qec-axis-spacer"></span>'
			. '<div class="qec-axis">' . $html . '</div>'
			. '<span class="qec-axis-spacer"></span>'
			. '</div>';
	}

	/**
	 * @return float[]
	 */
	private static function ticks( array $args ) {
		$min  = $args['scale_min'];
		$max  = $args['scale_max'];
		$step = $args['step'];

		if ( ( $max - $min ) / $step > self::MAX_TICKS ) {
			return array( $min, $max );
		}

		$ticks   = array();
		$epsilon = $step / 1000;
		for ( $i = 0; $min + $i * $step <= $max + $epsilon; $i++ ) {
			$ticks[] = round( $min + $i * $step, 6 );
		}
		return $ticks;
	}

	private static function value_text( array $args, array $item ) {
		if ( '' !== $item['value_text'] ) {
			return $item['value_text'];
		}

		$text = Helpers::format_number( $item['value'] );
		if ( null !== $item['max'] ) {
			$text .= $args['range_separator'] . Helpers::format_number( $item['max'] );
		}

		return $args['value_prefix'] . $text . $args['value_suffix'];
	}

	private static function percent( array $args, $value ) {
		$range   = $args['scale_max'] - $args['scale_min'];
		$percent = ( $value - $args['scale_min'] ) / $range * 100;
		return min( 100, max( 0, $percent ) );
	}

	/**
	 * Locale-independent CSS percentage.
	 */
	private static function css_percent( $percent ) {
		$number = rtrim( rtrim( sprintf( '%.4F', $percent ), '0' ), '.' );
		return ( '' === $number ? '0' : $number ) . '%';
	}
}
