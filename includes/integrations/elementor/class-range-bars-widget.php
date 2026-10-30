<?php
/**
 * Elementor widget: Range Bars.
 *
 * @package QaiyoEasyCharts
 */

namespace Qaiyo\EasyCharts\Integrations\Elementor;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use Qaiyo\EasyCharts\Charts\Range_Bars;
use Qaiyo\EasyCharts\Plugin;

defined( 'ABSPATH' ) || exit;

class Range_Bars_Widget extends Widget_Base {

	const ROOT = '{{WRAPPER}} .qec-range-bars';

	public function get_name() {
		return 'qaiyo-range-bars';
	}

	public function get_title() {
		return esc_html__( 'Range Bars', 'qaiyo-easy-charts' );
	}

	public function get_icon() {
		return 'eicon-skill-bar';
	}

	public function get_categories() {
		return array( 'qaiyo' );
	}

	public function get_keywords() {
		return array( 'qaiyo', 'chart', 'bar', 'range', 'progress', 'timeline' );
	}

	public function get_style_depends() {
		return array( Plugin::STYLE_HANDLE );
	}

	public function get_script_depends() {
		return array( Plugin::SCRIPT_HANDLE );
	}

	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	private function register_content_controls() {
		/* Items ----------------------------------------------------------- */
		$this->start_controls_section( 'section_items', array( 'label' => esc_html__( 'Bars', 'qaiyo-easy-charts' ) ) );

		$repeater = new Repeater();

		$repeater->add_control(
			'label',
			array(
				'label'       => esc_html__( 'Label', 'qaiyo-easy-charts' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Item', 'qaiyo-easy-charts' ),
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'value',
			array(
				'label'       => esc_html__( 'Value', 'qaiyo-easy-charts' ),
				'description' => esc_html__( 'End of the solid (primary) bar.', 'qaiyo-easy-charts' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 10,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'max',
			array(
				'label'       => esc_html__( 'Range end', 'qaiyo-easy-charts' ),
				'description' => esc_html__( 'Optional. Draws a highlighted range from Value to this number.', 'qaiyo-easy-charts' ),
				'type'        => Controls_Manager::NUMBER,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'value_text',
			array(
				'label'       => esc_html__( 'Value text', 'qaiyo-easy-charts' ),
				'description' => esc_html__( 'Optional. Overrides the text shown after the bar, e.g. "up to 5".', 'qaiyo-easy-charts' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$repeater->add_control(
			'primary_color',
			array(
				'label'     => esc_html__( 'Bar color', 'qaiyo-easy-charts' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
			)
		);

		$repeater->add_control(
			'secondary_color',
			array(
				'label' => esc_html__( 'Range color', 'qaiyo-easy-charts' ),
				'type'  => Controls_Manager::COLOR,
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Bars', 'qaiyo-easy-charts' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => Range_Bars::demo_items(),
				'title_field' => '{{{ label }}}',
			)
		);

		$this->end_controls_section();

		/* Scale ----------------------------------------------------------- */
		$this->start_controls_section( 'section_scale', array( 'label' => esc_html__( 'Scale & Axis', 'qaiyo-easy-charts' ) ) );

		$this->add_control(
			'scale_min',
			array(
				'label'   => esc_html__( 'Minimum', 'qaiyo-easy-charts' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0,
			)
		);

		$this->add_control(
			'scale_max',
			array(
				'label'       => esc_html__( 'Maximum', 'qaiyo-easy-charts' ),
				'description' => esc_html__( 'Leave empty to calculate from the data.', 'qaiyo-easy-charts' ),
				'type'        => Controls_Manager::NUMBER,
			)
		);

		$this->add_control(
			'step',
			array(
				'label'   => esc_html__( 'Axis step', 'qaiyo-easy-charts' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5,
				'min'     => 0,
			)
		);

		$this->add_control(
			'show_axis',
			array(
				'label'        => esc_html__( 'Show axis', 'qaiyo-easy-charts' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'unit',
			array(
				'label'     => esc_html__( 'Unit', 'qaiyo-easy-charts' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => 'munkanap',
				'condition' => array( 'show_axis' => 'yes' ),
			)
		);

		$this->add_control(
			'unit_position',
			array(
				'label'     => esc_html__( 'Show unit on', 'qaiyo-easy-charts' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'last',
				'options'   => array(
					'last' => esc_html__( 'Last tick', 'qaiyo-easy-charts' ),
					'all'  => esc_html__( 'Every tick', 'qaiyo-easy-charts' ),
					'none' => esc_html__( 'Hidden', 'qaiyo-easy-charts' ),
				),
				'condition' => array( 'show_axis' => 'yes' ),
			)
		);

		$this->end_controls_section();

		/* Values ---------------------------------------------------------- */
		$this->start_controls_section( 'section_values', array( 'label' => esc_html__( 'Values', 'qaiyo-easy-charts' ) ) );

		$this->add_control(
			'show_values',
			array(
				'label'        => esc_html__( 'Show values', 'qaiyo-easy-charts' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'range_separator',
			array(
				'label'     => esc_html__( 'Range separator', 'qaiyo-easy-charts' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '–',
				'condition' => array( 'show_values' => 'yes' ),
			)
		);

		$this->add_control(
			'value_prefix',
			array(
				'label'     => esc_html__( 'Prefix', 'qaiyo-easy-charts' ),
				'type'      => Controls_Manager::TEXT,
				'condition' => array( 'show_values' => 'yes' ),
			)
		);

		$this->add_control(
			'value_suffix',
			array(
				'label'     => esc_html__( 'Suffix', 'qaiyo-easy-charts' ),
				'type'      => Controls_Manager::TEXT,
				'condition' => array( 'show_values' => 'yes' ),
			)
		);

		$this->end_controls_section();

		/* Behaviour ------------------------------------------------------- */
		$this->start_controls_section( 'section_behaviour', array( 'label' => esc_html__( 'Layout & Animation', 'qaiyo-easy-charts' ) ) );

		$this->add_control(
			'layout',
			array(
				'label'       => esc_html__( 'Layout', 'qaiyo-easy-charts' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'auto',
				'options'     => array(
					'auto'    => esc_html__( 'Auto (stack when narrow)', 'qaiyo-easy-charts' ),
					'inline'  => esc_html__( 'Always in one row', 'qaiyo-easy-charts' ),
					'stacked' => esc_html__( 'Always stacked', 'qaiyo-easy-charts' ),
				),
				'description' => esc_html__( 'Auto stacks label and value above the bar when the chart is narrower than 560px.', 'qaiyo-easy-charts' ),
			)
		);

		$this->add_control(
			'animate',
			array(
				'label'        => esc_html__( 'Animate on scroll', 'qaiyo-easy-charts' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'hover_focus',
			array(
				'label'        => esc_html__( 'Highlight row on hover', 'qaiyo-easy-charts' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	private function register_style_controls() {
		/* Bars ------------------------------------------------------------ */
		$this->start_controls_section(
			'section_style_bars',
			array(
				'label' => esc_html__( 'Bars', 'qaiyo-easy-charts' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_color_control( 'bar_color', esc_html__( 'Bar color', 'qaiyo-easy-charts' ), '--qec-primary' );
		$this->add_color_control( 'range_color', esc_html__( 'Range color', 'qaiyo-easy-charts' ), '--qec-secondary' );
		$this->add_color_control( 'track_color', esc_html__( 'Track color', 'qaiyo-easy-charts' ), '--qec-track' );

		$this->add_size_control( 'bar_height', esc_html__( 'Bar height', 'qaiyo-easy-charts' ), '--qec-bar-height', array( 'px', 'em' ), 4, 120, 'before' );
		$this->add_size_control( 'bar_radius', esc_html__( 'Border radius', 'qaiyo-easy-charts' ), '--qec-bar-radius', array( 'px', 'em' ), 0, 60 );
		$this->add_size_control( 'row_gap', esc_html__( 'Space between bars', 'qaiyo-easy-charts' ), '--qec-row-gap', array( 'px', 'em' ), 0, 120 );
		$this->add_size_control( 'stack_gap', esc_html__( 'Label to bar spacing (stacked)', 'qaiyo-easy-charts' ), '--qec-stack-gap', array( 'px', 'em' ), 0, 60 );

		$this->end_controls_section();

		/* Labels ---------------------------------------------------------- */
		$this->start_controls_section(
			'section_style_labels',
			array(
				'label' => esc_html__( 'Labels', 'qaiyo-easy-charts' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_color_control( 'label_color', esc_html__( 'Color', 'qaiyo-easy-charts' ), '--qec-label-color' );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'selector' => self::ROOT . ' .qec-label',
			)
		);

		$this->add_size_control( 'label_width', esc_html__( 'Label column width', 'qaiyo-easy-charts' ), '--qec-label-width', array( 'px', '%', 'em' ), 0, 600 );
		$this->add_size_control( 'label_gap', esc_html__( 'Space before bar', 'qaiyo-easy-charts' ), '--qec-label-gap', array( 'px', 'em' ), 0, 120 );

		$this->end_controls_section();

		/* Values ---------------------------------------------------------- */
		$this->start_controls_section(
			'section_style_values',
			array(
				'label'     => esc_html__( 'Values', 'qaiyo-easy-charts' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_values' => 'yes' ),
			)
		);

		$this->add_color_control( 'value_color', esc_html__( 'Color', 'qaiyo-easy-charts' ), '--qec-value-color' );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'value_typography',
				'selector' => self::ROOT . ' .qec-value',
			)
		);

		$this->add_size_control( 'value_gap', esc_html__( 'Space after bar', 'qaiyo-easy-charts' ), '--qec-value-gap', array( 'px', 'em' ), 0, 120 );

		$this->end_controls_section();

		/* Axis ------------------------------------------------------------ */
		$this->start_controls_section(
			'section_style_axis',
			array(
				'label'     => esc_html__( 'Axis', 'qaiyo-easy-charts' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_axis' => 'yes' ),
			)
		);

		$this->add_color_control( 'axis_color', esc_html__( 'Color', 'qaiyo-easy-charts' ), '--qec-axis-color' );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'axis_typography',
				'selector' => self::ROOT . ' .qec-axis',
			)
		);

		$this->add_size_control( 'axis_gap', esc_html__( 'Space above axis', 'qaiyo-easy-charts' ), '--qec-axis-gap', array( 'px', 'em' ), 0, 120 );

		$this->end_controls_section();

		/* Animation ------------------------------------------------------- */
		$this->start_controls_section(
			'section_style_animation',
			array(
				'label'     => esc_html__( 'Animation', 'qaiyo-easy-charts' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'animate' => 'yes' ),
			)
		);

		$this->add_control(
			'duration',
			array(
				'label'      => esc_html__( 'Duration (ms)', 'qaiyo-easy-charts' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array( 'ms' => array( 'min' => 200, 'max' => 4000, 'step' => 50 ) ),
				'selectors'  => array( self::ROOT => '--qec-duration: {{SIZE}}ms;' ),
			)
		);

		$this->add_control(
			'stagger',
			array(
				'label'      => esc_html__( 'Delay between bars (ms)', 'qaiyo-easy-charts' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array( 'ms' => array( 'min' => 0, 'max' => 1000, 'step' => 10 ) ),
				'selectors'  => array( self::ROOT => '--qec-stagger: {{SIZE}}ms;' ),
			)
		);

		$this->end_controls_section();
	}

	private function add_color_control( $id, $label, $variable ) {
		$this->add_control(
			$id,
			array(
				'label'     => $label,
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( self::ROOT => $variable . ': {{VALUE}};' ),
			)
		);
	}

	private function add_size_control( $id, $label, $variable, array $units, $min, $max, $separator = 'default' ) {
		$range = array();
		foreach ( $units as $unit ) {
			$range[ $unit ] = 'px' === $unit ? array( 'min' => $min, 'max' => $max ) : array( 'min' => 0, 'max' => 'em' === $unit ? 10 : 100, 'step' => 'em' === $unit ? 0.1 : 1 );
		}

		$this->add_responsive_control(
			$id,
			array(
				'label'      => $label,
				'type'       => Controls_Manager::SLIDER,
				'size_units' => $units,
				'range'      => $range,
				'separator'  => $separator,
				'selectors'  => array( self::ROOT => $variable . ': {{SIZE}}{{UNIT}};' ),
			)
		);
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$args = array(
			'items'           => isset( $settings['items'] ) ? $settings['items'] : array(),
			'scale_min'       => $settings['scale_min'],
			'scale_max'       => $settings['scale_max'],
			'step'            => $settings['step'],
			'unit'            => $settings['unit'],
			'unit_position'   => $settings['unit_position'],
			'show_axis'       => 'yes' === $settings['show_axis'],
			'show_values'     => 'yes' === $settings['show_values'],
			'range_separator' => $settings['range_separator'],
			'value_prefix'    => $settings['value_prefix'],
			'value_suffix'    => $settings['value_suffix'],
			'layout'          => $settings['layout'],
			'animate'         => 'yes' === $settings['animate'],
			'hover_focus'     => 'yes' === $settings['hover_focus'],
		);

		// Markup is escaped inside the renderer.
		echo Range_Bars::render( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
