<?php
/**
 * Bricks element: Range Bars.
 *
 * Checkbox settings are phrased negatively ("Hide …", "Disable …") because
 * Bricks removes unchecked checkboxes from the saved settings.
 *
 * @package QaiyoEasyCharts
 */

namespace Qaiyo\EasyCharts\Integrations\Bricks;

use Qaiyo\EasyCharts\Charts\Range_Bars;
use Qaiyo\EasyCharts\Plugin;

defined( 'ABSPATH' ) || exit;

class Range_Bars_Element extends \Bricks\Element {

	public $category     = 'qaiyo';
	public $name         = 'qaiyo-range-bars';
	public $icon         = 'ti-bar-chart';
	public $css_selector = '';
	public $scripts      = array( 'qaiyoEasyChartsInit' );

	public function get_label() {
		return esc_html__( 'Range Bars', 'qaiyo-easy-charts' );
	}

	public function get_keywords() {
		return array( 'qaiyo', 'chart', 'bar', 'range', 'progress', 'timeline' );
	}

	public function enqueue_scripts() {
		Plugin::enqueue_assets();
	}

	public function set_control_groups() {
		$this->control_groups['bars']      = array(
			'title' => esc_html__( 'Bars', 'qaiyo-easy-charts' ),
			'tab'   => 'content',
		);
		$this->control_groups['scale']     = array(
			'title' => esc_html__( 'Scale & Axis', 'qaiyo-easy-charts' ),
			'tab'   => 'content',
		);
		$this->control_groups['values']    = array(
			'title' => esc_html__( 'Values', 'qaiyo-easy-charts' ),
			'tab'   => 'content',
		);
		$this->control_groups['behaviour'] = array(
			'title' => esc_html__( 'Layout & Animation', 'qaiyo-easy-charts' ),
			'tab'   => 'content',
		);
		$this->control_groups['style']     = array(
			'title' => esc_html__( 'Chart style', 'qaiyo-easy-charts' ),
			'tab'   => 'content',
		);
	}

	public function set_controls() {
		/* Bars ------------------------------------------------------------ */
		$this->controls['items'] = array(
			'tab'           => 'content',
			'group'         => 'bars',
			'type'          => 'repeater',
			'titleProperty' => 'label',
			'fields'        => array(
				'label'           => array(
					'label' => esc_html__( 'Label', 'qaiyo-easy-charts' ),
					'type'  => 'text',
				),
				'value'           => array(
					'label'       => esc_html__( 'Value', 'qaiyo-easy-charts' ),
					'type'        => 'number',
					'description' => esc_html__( 'End of the solid (primary) bar.', 'qaiyo-easy-charts' ),
				),
				'max'             => array(
					'label'       => esc_html__( 'Range end', 'qaiyo-easy-charts' ),
					'type'        => 'number',
					'description' => esc_html__( 'Optional. Draws a highlighted range from Value to this number.', 'qaiyo-easy-charts' ),
				),
				'value_text'      => array(
					'label'       => esc_html__( 'Value text', 'qaiyo-easy-charts' ),
					'type'        => 'text',
					'description' => esc_html__( 'Optional. Overrides the text shown after the bar, e.g. "up to 5".', 'qaiyo-easy-charts' ),
				),
				'primary_color'   => array(
					'label' => esc_html__( 'Bar color', 'qaiyo-easy-charts' ),
					'type'  => 'color',
				),
				'secondary_color' => array(
					'label' => esc_html__( 'Range color', 'qaiyo-easy-charts' ),
					'type'  => 'color',
				),
			),
			'default'       => Range_Bars::demo_items(),
		);

		/* Scale ----------------------------------------------------------- */
		$this->controls['scale_min'] = array(
			'tab'     => 'content',
			'group'   => 'scale',
			'label'   => esc_html__( 'Minimum', 'qaiyo-easy-charts' ),
			'type'    => 'number',
			'default' => 0,
		);

		$this->controls['scale_max'] = array(
			'tab'         => 'content',
			'group'       => 'scale',
			'label'       => esc_html__( 'Maximum', 'qaiyo-easy-charts' ),
			'type'        => 'number',
			'placeholder' => esc_html__( 'Auto', 'qaiyo-easy-charts' ),
			'description' => esc_html__( 'Leave empty to calculate from the data.', 'qaiyo-easy-charts' ),
		);

		$this->controls['step'] = array(
			'tab'     => 'content',
			'group'   => 'scale',
			'label'   => esc_html__( 'Axis step', 'qaiyo-easy-charts' ),
			'type'    => 'number',
			'min'     => 0,
			'default' => 5,
		);

		$this->controls['hide_axis'] = array(
			'tab'   => 'content',
			'group' => 'scale',
			'label' => esc_html__( 'Hide axis', 'qaiyo-easy-charts' ),
			'type'  => 'checkbox',
		);

		$this->controls['unit'] = array(
			'tab'      => 'content',
			'group'    => 'scale',
			'label'    => esc_html__( 'Unit', 'qaiyo-easy-charts' ),
			'type'     => 'text',
			'default'  => 'munkanap',
			'required' => array( 'hide_axis', '!=', true ),
		);

		$this->controls['unit_position'] = array(
			'tab'         => 'content',
			'group'       => 'scale',
			'label'       => esc_html__( 'Show unit on', 'qaiyo-easy-charts' ),
			'type'        => 'select',
			'inline'      => true,
			'options'     => array(
				'last' => esc_html__( 'Last tick', 'qaiyo-easy-charts' ),
				'all'  => esc_html__( 'Every tick', 'qaiyo-easy-charts' ),
				'none' => esc_html__( 'Hidden', 'qaiyo-easy-charts' ),
			),
			'placeholder' => esc_html__( 'Last tick', 'qaiyo-easy-charts' ),
			'required'    => array( 'hide_axis', '!=', true ),
		);

		/* Values ---------------------------------------------------------- */
		$this->controls['hide_values'] = array(
			'tab'   => 'content',
			'group' => 'values',
			'label' => esc_html__( 'Hide values', 'qaiyo-easy-charts' ),
			'type'  => 'checkbox',
		);

		$this->controls['range_separator'] = array(
			'tab'         => 'content',
			'group'       => 'values',
			'label'       => esc_html__( 'Range separator', 'qaiyo-easy-charts' ),
			'type'        => 'text',
			'inline'      => true,
			'placeholder' => '–',
			'required'    => array( 'hide_values', '!=', true ),
		);

		$this->controls['value_prefix'] = array(
			'tab'      => 'content',
			'group'    => 'values',
			'label'    => esc_html__( 'Prefix', 'qaiyo-easy-charts' ),
			'type'     => 'text',
			'inline'   => true,
			'required' => array( 'hide_values', '!=', true ),
		);

		$this->controls['value_suffix'] = array(
			'tab'      => 'content',
			'group'    => 'values',
			'label'    => esc_html__( 'Suffix', 'qaiyo-easy-charts' ),
			'type'     => 'text',
			'inline'   => true,
			'required' => array( 'hide_values', '!=', true ),
		);

		/* Behaviour ------------------------------------------------------- */
		$this->controls['layout'] = array(
			'tab'         => 'content',
			'group'       => 'behaviour',
			'label'       => esc_html__( 'Layout', 'qaiyo-easy-charts' ),
			'type'        => 'select',
			'options'     => array(
				'auto'    => esc_html__( 'Auto (stack when narrow)', 'qaiyo-easy-charts' ),
				'inline'  => esc_html__( 'Always in one row', 'qaiyo-easy-charts' ),
				'stacked' => esc_html__( 'Always stacked', 'qaiyo-easy-charts' ),
			),
			'placeholder' => esc_html__( 'Auto (stack when narrow)', 'qaiyo-easy-charts' ),
			'description' => esc_html__( 'Auto stacks label and value above the bar when the chart is narrower than 560px.', 'qaiyo-easy-charts' ),
		);

		$this->controls['disable_animation'] = array(
			'tab'   => 'content',
			'group' => 'behaviour',
			'label' => esc_html__( 'Disable scroll animation', 'qaiyo-easy-charts' ),
			'type'  => 'checkbox',
		);

		$this->controls['duration'] = array(
			'tab'         => 'content',
			'group'       => 'behaviour',
			'label'       => esc_html__( 'Duration (ms)', 'qaiyo-easy-charts' ),
			'type'        => 'number',
			'unit'        => 'ms',
			'placeholder' => 1100,
			'css'         => array( array( 'property' => '--qec-duration' ) ),
			'required'    => array( 'disable_animation', '!=', true ),
		);

		$this->controls['stagger'] = array(
			'tab'         => 'content',
			'group'       => 'behaviour',
			'label'       => esc_html__( 'Delay between bars (ms)', 'qaiyo-easy-charts' ),
			'type'        => 'number',
			'unit'        => 'ms',
			'placeholder' => 140,
			'css'         => array( array( 'property' => '--qec-stagger' ) ),
			'required'    => array( 'disable_animation', '!=', true ),
		);

		$this->controls['disable_hover_focus'] = array(
			'tab'   => 'content',
			'group' => 'behaviour',
			'label' => esc_html__( 'Disable row highlight on hover', 'qaiyo-easy-charts' ),
			'type'  => 'checkbox',
		);

		/* Style ----------------------------------------------------------- */
		$this->add_color_control( 'bar_color', esc_html__( 'Bar color', 'qaiyo-easy-charts' ), '--qec-primary' );
		$this->add_color_control( 'range_color', esc_html__( 'Range color', 'qaiyo-easy-charts' ), '--qec-secondary' );
		$this->add_color_control( 'track_color', esc_html__( 'Track color', 'qaiyo-easy-charts' ), '--qec-track' );

		$this->add_size_control( 'bar_height', esc_html__( 'Bar height', 'qaiyo-easy-charts' ), '--qec-bar-height', '28px' );
		$this->add_size_control( 'bar_radius', esc_html__( 'Border radius', 'qaiyo-easy-charts' ), '--qec-bar-radius', '0px' );
		$this->add_size_control( 'row_gap', esc_html__( 'Space between bars', 'qaiyo-easy-charts' ), '--qec-row-gap', '16px' );
		$this->add_size_control( 'stack_gap', esc_html__( 'Label to bar spacing (stacked)', 'qaiyo-easy-charts' ), '--qec-stack-gap', '8px' );

		$this->controls['label_separator'] = array(
			'tab'   => 'content',
			'group' => 'style',
			'label' => esc_html__( 'Labels', 'qaiyo-easy-charts' ),
			'type'  => 'separator',
		);
		$this->add_color_control( 'label_color', esc_html__( 'Color', 'qaiyo-easy-charts' ), '--qec-label-color' );
		$this->add_typography_control( 'label_typography', '.qec-label' );
		$this->add_size_control( 'label_width', esc_html__( 'Label column width', 'qaiyo-easy-charts' ), '--qec-label-width', esc_html__( 'Auto', 'qaiyo-easy-charts' ) );
		$this->add_size_control( 'label_gap', esc_html__( 'Space before bar', 'qaiyo-easy-charts' ), '--qec-label-gap', '40px' );

		$this->controls['value_separator'] = array(
			'tab'   => 'content',
			'group' => 'style',
			'label' => esc_html__( 'Values', 'qaiyo-easy-charts' ),
			'type'  => 'separator',
		);
		$this->add_color_control( 'value_color', esc_html__( 'Color', 'qaiyo-easy-charts' ), '--qec-value-color' );
		$this->add_typography_control( 'value_typography', '.qec-value' );
		$this->add_size_control( 'value_gap', esc_html__( 'Space after bar', 'qaiyo-easy-charts' ), '--qec-value-gap', '16px' );

		$this->controls['axis_separator'] = array(
			'tab'   => 'content',
			'group' => 'style',
			'label' => esc_html__( 'Axis', 'qaiyo-easy-charts' ),
			'type'  => 'separator',
		);
		$this->add_color_control( 'axis_color', esc_html__( 'Color', 'qaiyo-easy-charts' ), '--qec-axis-color' );
		$this->add_typography_control( 'axis_typography', '.qec-axis' );
		$this->add_size_control( 'axis_gap', esc_html__( 'Space above axis', 'qaiyo-easy-charts' ), '--qec-axis-gap', '20px' );
	}

	private function add_color_control( $id, $label, $variable ) {
		$this->controls[ $id ] = array(
			'tab'   => 'content',
			'group' => 'style',
			'label' => $label,
			'type'  => 'color',
			'css'   => array( array( 'property' => $variable ) ),
		);
	}

	private function add_size_control( $id, $label, $variable, $placeholder ) {
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

	private function add_typography_control( $id, $selector ) {
		$this->controls[ $id ] = array(
			'tab'   => 'content',
			'group' => 'style',
			'label' => esc_html__( 'Typography', 'qaiyo-easy-charts' ),
			'type'  => 'typography',
			'css'   => array(
				array(
					'property' => 'font',
					'selector' => $selector,
				),
			),
		);
	}

	public function render() {
		$settings = $this->settings;

		$args = Range_Bars::normalize(
			array(
				'items'           => isset( $settings['items'] ) ? $settings['items'] : array(),
				'scale_min'       => isset( $settings['scale_min'] ) ? $settings['scale_min'] : 0,
				'scale_max'       => isset( $settings['scale_max'] ) ? $settings['scale_max'] : null,
				'step'            => isset( $settings['step'] ) ? $settings['step'] : null,
				'unit'            => isset( $settings['unit'] ) ? $settings['unit'] : '',
				'unit_position'   => ! empty( $settings['unit_position'] ) ? $settings['unit_position'] : 'last',
				'show_axis'       => empty( $settings['hide_axis'] ),
				'show_values'     => empty( $settings['hide_values'] ),
				'range_separator' => isset( $settings['range_separator'] ) && '' !== $settings['range_separator'] ? $settings['range_separator'] : '–',
				'value_prefix'    => isset( $settings['value_prefix'] ) ? $settings['value_prefix'] : '',
				'value_suffix'    => isset( $settings['value_suffix'] ) ? $settings['value_suffix'] : '',
				'layout'          => ! empty( $settings['layout'] ) ? $settings['layout'] : 'auto',
				'animate'         => empty( $settings['disable_animation'] ),
				'hover_focus'     => empty( $settings['disable_hover_focus'] ),
			)
		);

		if ( empty( $args['items'] ) ) {
			return $this->render_element_placeholder(
				array( 'title' => esc_html__( 'Add at least one bar with a numeric value.', 'qaiyo-easy-charts' ) )
			);
		}

		$this->set_attribute( '_root', 'class', array_values( array_filter( Range_Bars::root_classes( $args ) ) ) );
		foreach ( Range_Bars::root_attributes( $args ) as $name => $value ) {
			if ( $value ) {
				$this->set_attribute( '_root', $name, true === $value ? '1' : $value );
			}
		}

		// Markup is escaped inside the renderer.
		echo "<div {$this->render_attributes( '_root' )}>" . Range_Bars::render_inner( $args ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
