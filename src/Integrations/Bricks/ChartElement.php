<?php
/**
 * Bricks element: Qaiyo Easy Chart.
 *
 * Checkbox settings are phrased negatively ("Hide …", "Disable …") because
 * Bricks removes unchecked checkboxes from the saved settings.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Integrations\Bricks;

use Qaiyo\EasyCharts\Chart\ChartConfig;
use Qaiyo\EasyCharts\Chart\Renderer;
use Qaiyo\EasyCharts\Chart\Repository;
use Qaiyo\EasyCharts\Frontend\Assets;
use Qaiyo\EasyCharts\Schema;
use Qaiyo\EasyCharts\Support\Cast;

defined( 'ABSPATH' ) || exit;

class ChartElement extends \Bricks\Element {

	public $category     = 'qaiyo';
	public $name         = Schema::ELEMENT;
	public $icon         = 'ti-bar-chart';
	public $css_selector = '';
	public $scripts      = array( 'qaiyoEasyChartsInit' );

	public function get_label() {
		return esc_html__( 'Qaiyo Easy Chart', 'qaiyo-easy-charts' );
	}

	public function get_keywords() {
		return array( 'qaiyo', 'chart', 'bar', 'column', 'range', 'graph' );
	}

	public function enqueue_scripts() {
		Assets::enqueue();
	}

	public function set_control_groups() {
		$groups = array(
			'data'      => esc_html__( 'Bars', 'qaiyo-easy-charts' ),
			'layout'    => esc_html__( 'Layout', 'qaiyo-easy-charts' ),
			'scale'     => esc_html__( 'Scale & axis', 'qaiyo-easy-charts' ),
			'values'    => esc_html__( 'Values & behaviour', 'qaiyo-easy-charts' ),
			'style'     => esc_html__( 'Chart style', 'qaiyo-easy-charts' ),
			'texts'     => esc_html__( 'Typography', 'qaiyo-easy-charts' ),
		);
		foreach ( $groups as $key => $title ) {
			$this->control_groups[ $key ] = array(
				'title' => $title,
				'tab'   => 'content',
			);
		}
	}

	public function set_controls() {
		$inline = array( 'source', '!=', 'saved' );

		/* Data ------------------------------------------------------------ */
		$this->controls['source'] = array(
			'tab'         => 'content',
			'group'       => 'data',
			'label'       => esc_html__( 'Data source', 'qaiyo-easy-charts' ),
			'type'        => 'select',
			'options'     => array(
				'inline' => esc_html__( 'Enter bars here', 'qaiyo-easy-charts' ),
				'saved'  => esc_html__( 'Saved chart', 'qaiyo-easy-charts' ),
			),
			'placeholder' => esc_html__( 'Enter bars here', 'qaiyo-easy-charts' ),
		);

		$charts = array();
		foreach ( Repository::list() as $chart ) {
			$charts[ (string) $chart['id'] ] = $chart['title'];
		}
		$this->controls['chart_id'] = array(
			'tab'         => 'content',
			'group'       => 'data',
			'label'       => esc_html__( 'Chart', 'qaiyo-easy-charts' ),
			'type'        => 'select',
			'options'     => $charts,
			'placeholder' => esc_html__( '— Select a chart —', 'qaiyo-easy-charts' ),
			'required'    => array( 'source', '=', 'saved' ),
		);

		$this->controls['items'] = array(
			'tab'           => 'content',
			'group'         => 'data',
			'type'          => 'repeater',
			'titleProperty' => 'label',
			'required'      => $inline,
			'fields'        => array(
				'label'       => array(
					'label' => esc_html__( 'Label', 'qaiyo-easy-charts' ),
					'type'  => 'text',
				),
				'value'       => array(
					'label' => esc_html__( 'Value', 'qaiyo-easy-charts' ),
					'type'  => 'number',
				),
				'max'         => array(
					'label'       => esc_html__( 'Range end', 'qaiyo-easy-charts' ),
					'type'        => 'number',
					'description' => esc_html__( 'Optional. Draws a highlighted range from Value to this number.', 'qaiyo-easy-charts' ),
				),
				'text'        => array(
					'label'       => esc_html__( 'Value text', 'qaiyo-easy-charts' ),
					'type'        => 'text',
					'description' => esc_html__( 'Optional. Replaces the automatic text, e.g. "up to 5".', 'qaiyo-easy-charts' ),
				),
				'color'       => array(
					'label' => esc_html__( 'Bar color', 'qaiyo-easy-charts' ),
					'type'  => 'color',
				),
				'range_color' => array(
					'label' => esc_html__( 'Range color', 'qaiyo-easy-charts' ),
					'type'  => 'color',
				),
			),
			'default'       => ChartConfig::demo_items(),
		);

		/* Layout ---------------------------------------------------------- */
		$this->controls['orientation'] = array(
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Orientation', 'qaiyo-easy-charts' ),
			'type'        => 'select',
			'options'     => array(
				'horizontal' => esc_html__( 'Horizontal bars', 'qaiyo-easy-charts' ),
				'vertical'   => esc_html__( 'Vertical columns', 'qaiyo-easy-charts' ),
				'auto'       => esc_html__( 'Columns, bars on narrow screens', 'qaiyo-easy-charts' ),
			),
			'placeholder' => esc_html__( 'Horizontal bars', 'qaiyo-easy-charts' ),
			'required'    => $inline,
		);
		$this->controls['layout'] = array(
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Labels of horizontal bars', 'qaiyo-easy-charts' ),
			'type'        => 'select',
			'options'     => array(
				'auto'    => esc_html__( 'Auto (stack when narrow)', 'qaiyo-easy-charts' ),
				'inline'  => esc_html__( 'Always in one row', 'qaiyo-easy-charts' ),
				'stacked' => esc_html__( 'Always above the bar', 'qaiyo-easy-charts' ),
			),
			'placeholder' => esc_html__( 'Auto (stack when narrow)', 'qaiyo-easy-charts' ),
			'required'    => $inline,
		);
		$this->controls['title'] = array(
			'tab'         => 'content',
			'group'       => 'layout',
			'label'       => esc_html__( 'Title', 'qaiyo-easy-charts' ),
			'type'        => 'text',
			'description' => esc_html__( 'Used as the accessible name of the chart.', 'qaiyo-easy-charts' ),
			'required'    => $inline,
		);
		$this->controls['description'] = array(
			'tab'      => 'content',
			'group'    => 'layout',
			'label'    => esc_html__( 'Description', 'qaiyo-easy-charts' ),
			'type'     => 'text',
			'required' => $inline,
		);
		$this->controls['show_caption'] = array(
			'tab'      => 'content',
			'group'    => 'layout',
			'label'    => esc_html__( 'Show title and description above the chart', 'qaiyo-easy-charts' ),
			'type'     => 'checkbox',
			'required' => $inline,
		);

		/* Scale ----------------------------------------------------------- */
		$this->controls['scale_min'] = array(
			'tab'      => 'content',
			'group'    => 'scale',
			'label'    => esc_html__( 'Minimum', 'qaiyo-easy-charts' ),
			'type'     => 'number',
			'default'  => 0,
			'required' => $inline,
		);
		$this->controls['scale_max'] = array(
			'tab'         => 'content',
			'group'       => 'scale',
			'label'       => esc_html__( 'Maximum', 'qaiyo-easy-charts' ),
			'type'        => 'number',
			'placeholder' => esc_html__( 'Auto', 'qaiyo-easy-charts' ),
			'description' => esc_html__( 'Leave empty to calculate from the data.', 'qaiyo-easy-charts' ),
			'required'    => $inline,
		);
		$this->controls['step'] = array(
			'tab'      => 'content',
			'group'    => 'scale',
			'label'    => esc_html__( 'Axis step', 'qaiyo-easy-charts' ),
			'type'     => 'number',
			'min'      => 0,
			'default'  => 5,
			'required' => $inline,
		);
		$this->controls['hide_axis'] = array(
			'tab'      => 'content',
			'group'    => 'scale',
			'label'    => esc_html__( 'Hide axis', 'qaiyo-easy-charts' ),
			'type'     => 'checkbox',
			'required' => $inline,
		);
		$this->controls['unit'] = array(
			'tab'      => 'content',
			'group'    => 'scale',
			'label'    => esc_html__( 'Unit', 'qaiyo-easy-charts' ),
			'type'     => 'text',
			'default'  => 'munkanap',
			'required' => $inline,
		);
		$this->controls['unit_position'] = array(
			'tab'         => 'content',
			'group'       => 'scale',
			'label'       => esc_html__( 'Show unit on', 'qaiyo-easy-charts' ),
			'type'        => 'select',
			'options'     => array(
				'last' => esc_html__( 'Last tick', 'qaiyo-easy-charts' ),
				'all'  => esc_html__( 'Every tick', 'qaiyo-easy-charts' ),
				'none' => esc_html__( 'Hidden', 'qaiyo-easy-charts' ),
			),
			'placeholder' => esc_html__( 'Last tick', 'qaiyo-easy-charts' ),
			'required'    => $inline,
		);

		/* Values & behaviour ---------------------------------------------- */
		$this->controls['hide_values'] = array(
			'tab'      => 'content',
			'group'    => 'values',
			'label'    => esc_html__( 'Hide values', 'qaiyo-easy-charts' ),
			'type'     => 'checkbox',
			'required' => $inline,
		);
		foreach (
			array(
				'separator' => esc_html__( 'Range separator', 'qaiyo-easy-charts' ),
				'prefix'    => esc_html__( 'Prefix', 'qaiyo-easy-charts' ),
				'suffix'    => esc_html__( 'Suffix', 'qaiyo-easy-charts' ),
			) as $key => $label
		) {
			$this->controls[ $key ] = array(
				'tab'         => 'content',
				'group'       => 'values',
				'label'       => $label,
				'type'        => 'text',
				'inline'      => true,
				'placeholder' => 'separator' === $key ? '–' : '',
				'required'    => $inline,
			);
		}
		$this->controls['disable_animation']   = array(
			'tab'      => 'content',
			'group'    => 'values',
			'label'    => esc_html__( 'Disable scroll animation', 'qaiyo-easy-charts' ),
			'type'     => 'checkbox',
			'required' => $inline,
		);
		$this->controls['disable_hover_focus'] = array(
			'tab'      => 'content',
			'group'    => 'values',
			'label'    => esc_html__( 'Disable bar highlight on hover', 'qaiyo-easy-charts' ),
			'type'     => 'checkbox',
			'required' => $inline,
		);
		$this->controls['disable_data_table']  = array(
			'tab'      => 'content',
			'group'    => 'values',
			'label'    => esc_html__( 'Disable the accessible data table', 'qaiyo-easy-charts' ),
			'type'     => 'checkbox',
			'required' => $inline,
		);

		/* Style ----------------------------------------------------------- */
		$this->color( 'bar_color', esc_html__( 'Bar color', 'qaiyo-easy-charts' ), '--qec-primary' );
		$this->color( 'range_color', esc_html__( 'Range color', 'qaiyo-easy-charts' ), '--qec-secondary' );
		$this->color( 'track_color', esc_html__( 'Track color', 'qaiyo-easy-charts' ), '--qec-track' );
		$this->color( 'label_color', esc_html__( 'Label color', 'qaiyo-easy-charts' ), '--qec-label-color' );
		$this->color( 'value_color', esc_html__( 'Value color', 'qaiyo-easy-charts' ), '--qec-value-color' );
		$this->color( 'axis_color', esc_html__( 'Axis color', 'qaiyo-easy-charts' ), '--qec-axis-color' );

		$this->size( 'bar_size', esc_html__( 'Bar thickness', 'qaiyo-easy-charts' ), '--qec-bar-size', '28px' );
		$this->size( 'chart_height', esc_html__( 'Column chart height', 'qaiyo-easy-charts' ), '--qec-chart-height', '280px' );
		$this->size( 'bar_radius', esc_html__( 'Corner radius', 'qaiyo-easy-charts' ), '--qec-bar-radius', '0px' );
		$this->size( 'row_gap', esc_html__( 'Space between bars', 'qaiyo-easy-charts' ), '--qec-row-gap', '16px' );
		$this->size( 'column_gap', esc_html__( 'Space between columns', 'qaiyo-easy-charts' ), '--qec-column-gap', '24px' );
		$this->size( 'label_width', esc_html__( 'Label column width', 'qaiyo-easy-charts' ), '--qec-label-width', esc_html__( 'Auto', 'qaiyo-easy-charts' ) );
		$this->size( 'label_gap', esc_html__( 'Space before bar', 'qaiyo-easy-charts' ), '--qec-label-gap', '40px' );
		$this->size( 'value_gap', esc_html__( 'Space after bar', 'qaiyo-easy-charts' ), '--qec-value-gap', '16px' );

		$this->controls['duration'] = array(
			'tab'         => 'content',
			'group'       => 'style',
			'label'       => esc_html__( 'Duration (ms)', 'qaiyo-easy-charts' ),
			'type'        => 'number',
			'unit'        => 'ms',
			'placeholder' => 1100,
			'css'         => array( array( 'property' => '--qec-duration' ) ),
		);

		/* Typography ------------------------------------------------------ */
		foreach (
			array(
				'chart_typography' => array( esc_html__( 'Chart font', 'qaiyo-easy-charts' ), '' ),
				'label_typography' => array( esc_html__( 'Labels', 'qaiyo-easy-charts' ), '.qec-label' ),
				'value_typography' => array( esc_html__( 'Values', 'qaiyo-easy-charts' ), '.qec-value' ),
				'axis_typography'  => array( esc_html__( 'Axis', 'qaiyo-easy-charts' ), '.qec-axis' ),
				'title_typography' => array( esc_html__( 'Title', 'qaiyo-easy-charts' ), '.qec-title' ),
			) as $key => $spec
		) {
			$css = array( 'property' => 'font' );
			if ( '' !== $spec[1] ) {
				$css['selector'] = $spec[1];
			}
			$this->controls[ $key ] = array(
				'tab'   => 'content',
				'group' => 'texts',
				'label' => $spec[0],
				'type'  => 'typography',
				'css'   => array( $css ),
			);
		}
	}

	private function color( string $id, string $label, string $variable ): void {
		$this->controls[ $id ] = array(
			'tab'   => 'content',
			'group' => 'style',
			'label' => $label,
			'type'  => 'color',
			'css'   => array( array( 'property' => $variable ) ),
		);
	}

	private function size( string $id, string $label, string $variable, string $placeholder ): void {
		$this->controls[ $id ] = array(
			'tab'         => 'content',
			'group'       => 'style',
			'label'       => $label,
			'type'        => 'number',
			'units'       => true,
			'placeholder' => $placeholder,
			'css'         => array( array( 'property' => $variable ) ),
		);
	}

	/**
	 * @return array<string,mixed>|null
	 */
	private function config(): ?array {
		$s = Cast::arr( $this->settings );

		if ( 'saved' === ( $s['source'] ?? '' ) ) {
			$config = Repository::find( Cast::int( $s['chart_id'] ?? 0 ) );
			if ( null === $config ) {
				return null;
			}
			// Values set in the Bricks style controls win over the saved chart's look.
			foreach ( array( 'bar_color', 'range_color', 'track_color', 'label_color', 'value_color', 'axis_color', 'bar_size', 'chart_height' ) as $key ) {
				if ( ! empty( $s[ $key ] ) ) {
					$config[ $key ] = '';
				}
			}
			if ( ! empty( $s['bar_radius'] ) ) {
				$config['radius'] = '';
			}
			if ( ! empty( $s['chart_typography']['font-family'] ) ) {
				$config['font_family'] = '';
			}
			return $config;
		}

		return ChartConfig::sanitize(
			array(
				'items'         => Cast::arr( $s['items'] ?? array() ),
				'orientation'   => ! empty( $s['orientation'] ) ? $s['orientation'] : 'horizontal',
				'layout'        => ! empty( $s['layout'] ) ? $s['layout'] : 'auto',
				'title'         => $s['title'] ?? '',
				'description'   => $s['description'] ?? '',
				'show_caption'  => ! empty( $s['show_caption'] ),
				'scale_min'     => $s['scale_min'] ?? 0,
				'scale_max'     => $s['scale_max'] ?? '',
				'step'          => $s['step'] ?? '',
				'unit'          => $s['unit'] ?? '',
				'unit_position' => ! empty( $s['unit_position'] ) ? $s['unit_position'] : 'last',
				'show_axis'     => empty( $s['hide_axis'] ),
				'show_values'   => empty( $s['hide_values'] ),
				'separator'     => isset( $s['separator'] ) && '' !== $s['separator'] ? $s['separator'] : '–',
				'prefix'        => $s['prefix'] ?? '',
				'suffix'        => $s['suffix'] ?? '',
				'animate'       => empty( $s['disable_animation'] ),
				'hover_focus'   => empty( $s['disable_hover_focus'] ),
				'data_table'    => empty( $s['disable_data_table'] ),
			)
		);
	}

	public function render() {
		$config = $this->config();
		$config = null === $config ? null : Renderer::filter_config( $config );
		if ( null === $config || array() === $config['items'] ) {
			return $this->render_element_placeholder(
				array( 'title' => esc_html__( 'Select a saved chart or add at least one bar.', 'qaiyo-easy-charts' ) )
			);
		}

		Assets::enqueue();

		$this->set_attribute( '_root', 'class', Renderer::root_classes( $config ) );
		$style = Renderer::root_style( $config );
		if ( '' !== $style ) {
			$this->set_attribute( '_root', 'style', $style );
		}
		if ( $config['animate'] ) {
			$this->set_attribute( '_root', 'data-qec-animate', '1' );
		}
		if ( ! $config['show_caption'] && '' !== $config['title'] ) {
			$this->set_attribute( '_root', 'aria-label', $config['title'] );
		}

		// Inner markup is escaped inside the renderer.
		echo "<figure {$this->render_attributes( '_root' )}>" . Renderer::inner( $config ) . '</figure>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
