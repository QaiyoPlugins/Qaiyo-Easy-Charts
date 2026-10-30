<?php
/**
 * KPI rings: one progress ring per item.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Chart\Types;

use Qaiyo\EasyCharts\Chart\Renderer;
use Qaiyo\EasyCharts\Support\Css;

defined( 'ABSPATH' ) || exit;

/**
 * Each item is a ring filled to value / goal, with the value in the middle and
 * the label below. The goal is the item's second value, else the scale
 * maximum, else 100 (so plain percentages need no goal at all).
 *
 * @phpstan-import-type QecConfig from \Qaiyo\EasyCharts\Chart\ChartConfig
 * @phpstan-import-type QecItem from \Qaiyo\EasyCharts\Chart\ChartConfig
 * @phpstan-import-type QecTable from \Qaiyo\EasyCharts\Chart\ChartConfig
 */
final class RingChart implements ChartType {

	/**
	 * Escaped markup of the drawn chart.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array $tips   One sentence per item.
	 * @phpstan-param list<string> $tips
	 */
	public static function visual( array $config, array $tips ): string {
		$hidden = Renderer::hidden( $config );

		$html = '<div class="qec-rings"' . Renderer::list_attrs( $config ) . '>';
		foreach ( $config['items'] as $index => $item ) {
			$goal    = self::goal( $config, $item );
			$percent = $goal > 0 ? min( 100.0, max( 0.0, (float) $item['value'] / $goal * 100 ) ) : 0.0;

			$html .= Renderer::mark_open(
				$config,
				'div',
				'qec-ring',
				array(
					'--qec-i'         => (string) $index,
					'--qec-p'         => rtrim( Css::percent( $percent ), '%' ),
					'--qec-primary'   => $item['color'],
					'--qec-secondary' => $item['range_color'],
				)
			);
			$html .= '<span class="qec-ring-figure"' . $hidden . '>'
				. '<svg class="qec-ring-svg" viewBox="0 0 36 36" aria-hidden="true" focusable="false">'
				. '<circle class="qec-ring-track" cx="18" cy="18" r="15.5" pathLength="100"></circle>'
				. '<circle class="qec-ring-bar" cx="18" cy="18" r="15.5" pathLength="100"></circle>'
				. '</svg>';
			if ( $config['show_values'] ) {
				$html .= '<span class="qec-value qec-count">' . esc_html( Renderer::value_text( $config, $item ) ) . '</span>';
			}
			$html .= '</span>';
			$html .= '<span class="qec-label"' . $hidden . '>' . esc_html( $item['label'] ) . '</span>';
			$html .= Renderer::mark_close( $config, 'div', $tips[ $index ] ?? '' );
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
		$rows = array();
		foreach ( $config['items'] as $item ) {
			$goal   = self::goal( $config, $item );
			$rows[] = array(
				$item['label'],
				Renderer::with_unit( $config, Renderer::value_text( $config, $item ) ),
				Renderer::with_unit( $config, Renderer::number_text( $config, $goal ) ),
				Renderer::share( (float) $item['value'], $goal ),
			);
		}

		return array(
			'head' => array( __( 'Item', 'qaiyo-easy-charts' ), __( 'Value', 'qaiyo-easy-charts' ), __( 'Goal', 'qaiyo-easy-charts' ), __( 'Progress', 'qaiyo-easy-charts' ) ),
			'rows' => $rows,
			'note' => '',
		);
	}

	/**
	 * The full ring's value.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array $item   Item.
	 * @phpstan-param QecItem $item
	 */
	private static function goal( array $config, array $item ): float {
		if ( '' !== $item['max'] ) {
			return (float) $item['max'];
		}
		return '' !== $config['scale_max'] ? (float) $config['scale_max'] : 100.0;
	}
}
