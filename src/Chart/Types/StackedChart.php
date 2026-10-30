<?php
/**
 * 100% stacked bar: how a whole splits into parts.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Chart\Types;

use Qaiyo\EasyCharts\Chart\Renderer;
use Qaiyo\EasyCharts\Support\Css;

defined( 'ABSPATH' ) || exit;

/**
 * One full-width bar split by the items' shares, and a legend with each
 * item's value and percentage. Colours cycle through a six-colour palette
 * unless an item has its own.
 *
 * @phpstan-import-type QecConfig from \Qaiyo\EasyCharts\Chart\ChartConfig
 * @phpstan-import-type QecTable from \Qaiyo\EasyCharts\Chart\ChartConfig
 */
final class StackedChart implements ChartType {

	/**
	 * Escaped markup of the drawn chart.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array $tips   One sentence per item.
	 * @phpstan-param list<string> $tips
	 */
	public static function visual( array $config, array $tips ): string {
		$total  = self::total( $config );
		$hidden = Renderer::hidden( $config );

		$bar    = '';
		$legend = '';
		$start  = 0.0;
		foreach ( $config['items'] as $index => $item ) {
			$size = $total > 0 ? max( 0.0, (float) $item['value'] ) / $total * 100 : 0.0;
			$vars = array(
				'--qec-i' => (string) $index,
				'--qec-c' => $item['color'],
			);

			$bar   .= sprintf(
				'<span class="qec-seg qec-stack-seg" style="%s"></span>',
				esc_attr( Css::inline( $vars ) . '--qec-start:' . Css::percent( $start ) . ';--qec-size:' . Css::percent( $size ) . ';' )
			);
			$start += $size;

			$legend .= Renderer::mark_open( $config, 'div', 'qec-legend-item', $vars );
			$legend .= '<span class="qec-swatch"' . ( '' === $hidden ? ' aria-hidden="true"' : $hidden ) . '></span>';
			$legend .= '<span class="qec-label"' . $hidden . '>' . esc_html( $item['label'] ) . '</span>';
			if ( $config['show_values'] ) {
				$text  = Renderer::value_text( $config, $item );
				$share = Renderer::share( max( 0.0, (float) $item['value'] ), $total );

				$legend .= '<span class="qec-value"' . $hidden . '><span class="qec-count">' . esc_html( $text ) . '</span>';
				// Values that already are percentages need no second percentage.
				if ( $share !== $text ) {
					$legend .= ' <span class="qec-share qec-count">' . esc_html( $share ) . '</span>';
				}
				$legend .= '</span>';
			}
			$legend .= Renderer::mark_close( $config, 'div', $tips[ $index ] ?? '' );
		}

		return '<div class="qec-stack">'
			. '<div class="qec-stack-track" aria-hidden="true">' . $bar . '</div>'
			. '<div class="qec-legend qec-legend--stack"' . Renderer::list_attrs( $config ) . '>' . $legend . '</div>'
			. '</div>';
	}

	/**
	 * The chart's data as table cells.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @return QecTable
	 */
	public static function table( array $config ): array {
		$total = self::total( $config );
		$rows  = array();
		foreach ( $config['items'] as $item ) {
			$rows[] = array(
				$item['label'],
				Renderer::with_unit( $config, Renderer::value_text( $config, $item ) ),
				Renderer::share( max( 0.0, (float) $item['value'] ), $total ),
			);
		}

		return array(
			'head' => array( __( 'Item', 'qaiyo-easy-charts' ), __( 'Value', 'qaiyo-easy-charts' ), __( 'Share', 'qaiyo-easy-charts' ) ),
			'rows' => $rows,
			'note' => '',
		);
	}

	/**
	 * Sum of the (non-negative) values.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 */
	private static function total( array $config ): float {
		$total = 0.0;
		foreach ( $config['items'] as $item ) {
			$total += max( 0.0, (float) $item['value'] );
		}
		return $total;
	}
}
