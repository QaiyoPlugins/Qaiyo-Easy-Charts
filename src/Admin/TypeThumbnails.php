<?php
/**
 * Small SVG mock-ups of the chart types for the visual type picker.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Admin;

use Qaiyo\EasyCharts\Chart\ChartConfig;

defined( 'ABSPATH' ) || exit;

/**
 * One decorative thumbnail per chart type. The shapes use three classes
 * (qec-ti-a: bars, qec-ti-b: second colour, qec-ti-t: tracks) so the admin
 * stylesheet colours them for the light and the selected state.
 */
final class TypeThumbnails {

	/**
	 * Inner SVG markup per type, drawn on a 120 × 72 canvas.
	 */
	const SHAPES = array(
		'bars'     => '<rect class="qec-ti-t" x="38" y="12" width="70" height="10" rx="2"/><rect class="qec-ti-a" x="38" y="12" width="20" height="10" rx="2"/><rect class="qec-ti-t" x="38" y="31" width="70" height="10" rx="2"/><rect class="qec-ti-a" x="38" y="31" width="34" height="10" rx="2"/><rect class="qec-ti-b" x="72" y="31" width="18" height="10"/><rect class="qec-ti-t" x="38" y="50" width="70" height="10" rx="2"/><rect class="qec-ti-a" x="38" y="50" width="34" height="10" rx="2"/><rect class="qec-ti-b" x="72" y="50" width="36" height="10" rx="2"/><rect class="qec-ti-a" x="10" y="14" width="20" height="5" rx="2.5"/><rect class="qec-ti-a" x="10" y="33" width="22" height="5" rx="2.5"/><rect class="qec-ti-a" x="10" y="52" width="18" height="5" rx="2.5"/>',
		'compare'  => '<rect class="qec-ti-a" x="10" y="10" width="54" height="7" rx="2"/><rect class="qec-ti-b" x="10" y="19" width="78" height="7" rx="2"/><rect class="qec-ti-a" x="10" y="33" width="80" height="7" rx="2"/><rect class="qec-ti-b" x="10" y="42" width="62" height="7" rx="2"/><rect class="qec-ti-a" x="10" y="56" width="36" height="7" rx="2"/><rect class="qec-ti-b" x="10" y="65" width="96" height="5" rx="2"/>',
		'ring'     => '<circle class="qec-ti-ring-t" cx="36" cy="36" r="20"/><circle class="qec-ti-ring" cx="36" cy="36" r="20" stroke-dasharray="100 126" transform="rotate(-90 36 36)"/><circle class="qec-ti-ring-t" cx="84" cy="36" r="20"/><circle class="qec-ti-ring qec-ti-ring-b" cx="84" cy="36" r="20" stroke-dasharray="70 126" transform="rotate(-90 84 36)"/><rect class="qec-ti-a" x="29" y="30" width="14" height="7" rx="2"/><rect class="qec-ti-t3" x="30" y="40" width="12" height="3" rx="1.5"/><rect class="qec-ti-a" x="77" y="30" width="14" height="7" rx="2"/><rect class="qec-ti-t3" x="78" y="40" width="12" height="3" rx="1.5"/>',
		'timeline' => '<rect class="qec-ti-t" x="8" y="10" width="104" height="9" rx="2"/><rect class="qec-ti-a" x="8" y="10" width="30" height="9" rx="2"/><rect class="qec-ti-t" x="8" y="27" width="104" height="9" rx="2"/><rect class="qec-ti-a" x="30" y="27" width="40" height="9" rx="2"/><rect class="qec-ti-t" x="8" y="44" width="104" height="9" rx="2"/><rect class="qec-ti-a" x="58" y="44" width="40" height="9" rx="2"/><path class="qec-ti-b" d="M104 57l6 6-6 6-6-6z"/>',
		'bullet'   => '<rect class="qec-ti-t" x="10" y="14" width="100" height="16" rx="2"/><rect class="qec-ti-t2" x="10" y="14" width="64" height="16" rx="2"/><rect class="qec-ti-a" x="10" y="19" width="56" height="6" rx="1"/><rect class="qec-ti-a" x="82" y="11" width="3" height="22"/><rect class="qec-ti-t" x="10" y="42" width="100" height="16" rx="2"/><rect class="qec-ti-t2" x="10" y="42" width="52" height="16" rx="2"/><rect class="qec-ti-a" x="10" y="47" width="78" height="6" rx="1"/><rect class="qec-ti-a" x="70" y="39" width="3" height="22"/>',
		'stacked'  => '<rect class="qec-ti-a" x="8" y="16" width="50" height="16" rx="2"/><rect class="qec-ti-b" x="60" y="16" width="26" height="16"/><rect class="qec-ti-c" x="88" y="16" width="14" height="16"/><rect class="qec-ti-d" x="104" y="16" width="8" height="16" rx="2"/><rect class="qec-ti-a" x="8" y="44" width="7" height="7" rx="1.5"/><rect class="qec-ti-t3" x="18" y="45" width="22" height="5" rx="2.5"/><rect class="qec-ti-b" x="48" y="44" width="7" height="7" rx="1.5"/><rect class="qec-ti-t3" x="58" y="45" width="20" height="5" rx="2.5"/><rect class="qec-ti-c" x="8" y="56" width="7" height="7" rx="1.5"/><rect class="qec-ti-t3" x="18" y="57" width="18" height="5" rx="2.5"/>',
		'rating'   => '<rect class="qec-ti-a" x="8" y="14" width="26" height="14" rx="3"/><path class="qec-ti-b" d="M10 38l2 4 4 .5-3 3 .8 4.5-3.8-2-3.8 2 .8-4.5-3-3 4-.5zM24 38l2 4 4 .5-3 3 .8 4.5-3.8-2-3.8 2 .8-4.5-3-3 4-.5z"/><rect class="qec-ti-t" x="46" y="10" width="64" height="6" rx="3"/><rect class="qec-ti-a" x="46" y="10" width="48" height="6" rx="3"/><rect class="qec-ti-t" x="46" y="22" width="64" height="6" rx="3"/><rect class="qec-ti-a" x="46" y="22" width="22" height="6" rx="3"/><rect class="qec-ti-t" x="46" y="34" width="64" height="6" rx="3"/><rect class="qec-ti-a" x="46" y="34" width="10" height="6" rx="3"/><rect class="qec-ti-t" x="46" y="46" width="64" height="6" rx="3"/><rect class="qec-ti-a" x="46" y="46" width="5" height="6" rx="3"/><rect class="qec-ti-t" x="46" y="58" width="64" height="6" rx="3"/><rect class="qec-ti-a" x="46" y="58" width="7" height="6" rx="3"/>',
	);

	/**
	 * The thumbnail of a chart type (decorative: hidden from assistive technology).
	 *
	 * @param string $type Chart type.
	 */
	public static function svg( string $type ): string {
		$shapes = self::SHAPES[ $type ] ?? self::SHAPES['bars'];
		return '<svg class="qec-type-thumb" viewBox="0 0 120 72" width="120" height="72" aria-hidden="true" focusable="false">' . $shapes . '</svg>';
	}

	/**
	 * Tags and attributes the thumbnails use, for wp_kses().
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function allowed_html(): array {
		$shape = array(
			'class'            => true,
			'x'                => true,
			'y'                => true,
			'width'            => true,
			'height'           => true,
			'rx'               => true,
			'cx'               => true,
			'cy'               => true,
			'r'                => true,
			'd'                => true,
			'transform'        => true,
			'stroke-dasharray' => true,
		);
		return array(
			'svg'    => array(
				'class'       => true,
				'viewbox'     => true,
				'width'       => true,
				'height'      => true,
				'aria-hidden' => true,
				'focusable'   => true,
			),
			'rect'   => $shape,
			'circle' => $shape,
			'path'   => $shape,
		);
	}

	/**
	 * Every type with its name and thumbnail, for the block editor.
	 *
	 * @return array<string, array{name: string, help: string, svg: string}>
	 */
	public static function all(): array {
		$types = array();
		foreach ( ChartConfig::type_labels() as $type => $labels ) {
			$types[ $type ] = array(
				'name' => $labels['name'],
				'help' => $labels['help'],
				'svg'  => self::svg( $type ),
			);
		}
		return $types;
	}
}
