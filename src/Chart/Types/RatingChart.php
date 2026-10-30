<?php
/**
 * Rating distribution: an average with stars and one bar per rating level.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Chart\Types;

use Qaiyo\EasyCharts\Chart\Renderer;
use Qaiyo\EasyCharts\Support\Css;

defined( 'ABSPATH' ) || exit;

/**
 * Items are rating levels ("5", "4", … or any label) with their counts. A
 * numeric label is the level itself; otherwise the first item is the best
 * level and each next one is one less. The summary shows the weighted average,
 * filled stars and the total; each row shows the level's share.
 *
 * @phpstan-import-type QecConfig from \Qaiyo\EasyCharts\Chart\ChartConfig
 * @phpstan-import-type QecTable from \Qaiyo\EasyCharts\Chart\ChartConfig
 */
final class RatingChart implements ChartType {

	/** Upper bound of stars drawn, whatever the levels are. */
	const MAX_STARS = 10;

	const STAR_PATH = 'M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.6L12 17.3l-5.9 3.3 1.3-6.6-4.9-4.6 6.6-.8z';

	/**
	 * Escaped markup of the drawn chart.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array $tips   One sentence per item.
	 * @phpstan-param list<string> $tips
	 */
	public static function visual( array $config, array $tips ): string {
		$stats  = self::stats( $config );
		$hidden = Renderer::hidden( $config );

		$stars = str_repeat( '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="' . self::STAR_PATH . '"></path></svg>', $stats['stars'] );
		$fill  = $stats['stars'] > 0 ? $stats['average'] / $stats['stars'] * 100 : 0.0;

		$html  = '<div class="qec-rating">';
		$html .= '<div class="qec-rating-summary"' . ( Renderer::is_list( $config ) ? '' : ' aria-hidden="true"' ) . '>';
		$html .= '<span class="qec-rating-average qec-count"' . $hidden . '>' . esc_html( Renderer::format_number( round( $stats['average'], 1 ) ) ) . '</span>';
		$html .= '<span class="qec-stars" style="' . esc_attr( '--qec-fill:' . Css::percent( $fill ) ) . '" aria-hidden="true">'
			. '<span class="qec-stars-base">' . $stars . '</span><span class="qec-stars-fill">' . $stars . '</span></span>';
		$html .= '<span class="qec-rating-total"' . $hidden . '>' . esc_html( Renderer::with_unit( $config, Renderer::format_number( $stats['total'] ) ) ) . '</span>';
		if ( Renderer::is_list( $config ) ) {
			$html .= '<span class="qec-sr-only">' . esc_html( self::note( $stats ) ) . '</span>';
		}
		$html .= '</div>';

		$html .= '<div class="qec-grid"' . Renderer::list_attrs( $config ) . '>';
		foreach ( $config['items'] as $index => $item ) {
			$share = $stats['total'] > 0 ? max( 0.0, (float) $item['value'] ) / $stats['total'] * 100 : 0.0;

			$html .= Renderer::mark_open(
				$config,
				'div',
				'qec-row',
				array(
					'--qec-i'            => (string) $index,
					'--qec-item-primary' => $item['color'],
				)
			);
			$html .= '<span class="qec-label"' . $hidden . '>' . esc_html( $item['label'] ) . '</span>';
			$html .= '<span class="qec-track" aria-hidden="true"><span class="qec-seg qec-seg--primary" style="' . esc_attr( '--qec-start:0%;--qec-size:' . Css::percent( $share ) ) . '"></span></span>';
			if ( $config['show_values'] ) {
				$text  = '' !== $item['text'] ? $item['text'] : Renderer::share( max( 0.0, (float) $item['value'] ), $stats['total'] );
				$html .= '<span class="qec-value qec-count"' . $hidden . '>' . esc_html( $text ) . '</span>';
			}
			$html .= Renderer::mark_close( $config, 'div', $tips[ $index ] ?? '' );
		}

		return $html . '</div></div>';
	}

	/**
	 * The chart's data as table cells.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @return QecTable
	 */
	public static function table( array $config ): array {
		$stats = self::stats( $config );
		$rows  = array();
		foreach ( $config['items'] as $item ) {
			$rows[] = array(
				$item['label'],
				Renderer::format_number( max( 0.0, (float) $item['value'] ) ),
				Renderer::share( max( 0.0, (float) $item['value'] ), $stats['total'] ),
			);
		}

		return array(
			'head' => array( __( 'Rating', 'qaiyo-easy-charts' ), __( 'Count', 'qaiyo-easy-charts' ), __( 'Share', 'qaiyo-easy-charts' ) ),
			'rows' => $rows,
			'note' => array() === $rows ? '' : self::note( $stats ),
		);
	}

	/**
	 * "Average: 4.6 / 5", the summary in words.
	 *
	 * @param array{average: float, total: float, stars: int} $stats Statistics.
	 */
	private static function note( array $stats ): string {
		return sprintf(
			/* translators: 1: average rating, 2: best possible rating. */
			__( 'Average: %1$s / %2$s', 'qaiyo-easy-charts' ),
			Renderer::format_number( round( $stats['average'], 1 ) ),
			Renderer::format_number( $stats['stars'] )
		);
	}

	/**
	 * Weighted average, total count and the number of stars to draw.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @return array{average: float, total: float, stars: int}
	 */
	private static function stats( array $config ): array {
		$count  = count( $config['items'] );
		$total  = 0.0;
		$sum    = 0.0;
		$levels = array();
		foreach ( $config['items'] as $index => $item ) {
			$level    = is_numeric( str_replace( ',', '.', trim( $item['label'], " \t\n\r\0\x0B★*" ) ) )
				? (float) str_replace( ',', '.', trim( $item['label'], " \t\n\r\0\x0B★*" ) )
				: (float) ( $count - $index );
			$levels[] = $level;
			$weight   = max( 0.0, (float) $item['value'] );
			$total   += $weight;
			$sum     += $level * $weight;
		}

		$best = array() === $levels ? 5.0 : max( $levels );

		return array(
			'average' => $total > 0 ? $sum / $total : 0.0,
			'total'   => $total,
			'stars'   => (int) min( self::MAX_STARS, max( 1, (int) ceil( $best ) ) ),
		);
	}
}
