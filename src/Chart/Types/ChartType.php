<?php
/**
 * Contract of a chart type.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Chart\Types;

defined( 'ABSPATH' ) || exit;

/**
 * A chart type draws the visual part of a chart and describes its data as a
 * table. The renderer builds everything else (figure, caption, lazy loading,
 * export, the hidden data table, tooltips and screen-reader text) from these two.
 *
 * @phpstan-import-type QecConfig from \Qaiyo\EasyCharts\Chart\ChartConfig
 * @phpstan-import-type QecTable from \Qaiyo\EasyCharts\Chart\ChartConfig
 */
interface ChartType {

	/**
	 * Escaped markup of the drawn chart.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @param array $tips   One sentence per item, in item order.
	 * @phpstan-param list<string> $tips
	 */
	public static function visual( array $config, array $tips ): string;

	/**
	 * The chart's data as plain-text table cells: one row per item, the first
	 * cell being the item's name.
	 *
	 * @param array $config Sanitized configuration.
	 * @phpstan-param QecConfig $config
	 * @return QecTable
	 */
	public static function table( array $config ): array;
}
