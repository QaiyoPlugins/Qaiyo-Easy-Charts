<?php
/**
 * Elementor widget: Qaiyo Easy Chart.
 *
 * Content controls map to the shared chart configuration; style controls only
 * set CSS custom properties / typography on the chart root.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Integrations\Elementor;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use Qaiyo\EasyCharts\Chart\ChartConfig;
use Qaiyo\EasyCharts\Chart\Renderer;
use Qaiyo\EasyCharts\Chart\Repository;
use Qaiyo\EasyCharts\Schema;
use Qaiyo\EasyCharts\Support\Cast;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor widget that renders a chart.
 *
 * @phpstan-import-type QecConfig from ChartConfig
 */
class ChartWidget extends Widget_Base {

	const ROOT = '{{WRAPPER}} .qec-chart';

	/**
	 * Widget name (stored in page data).
	 *
	 * @return string
	 */
	public function get_name() {
		return Schema::ELEMENT;
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Qaiyo Easy Chart', 'qaiyo-easy-charts' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-skill-bar';
	}

	/**
	 * Widget categories.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( 'qaiyo', 'general' );
	}

	/**
	 * Search keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'qaiyo', 'chart', 'bar', 'column', 'range', 'graph' );
	}

	/**
	 * Style handles the widget needs.
	 *
	 * @return string[]
	 */
	public function get_style_depends() {
		return array( Schema::HANDLE_CHART );
	}

	/**
	 * Script handles the widget needs.
	 *
	 * @return string[]
	 */
	public function get_script_depends() {
		return array( Schema::HANDLE_CHART );
	}

	/**
	 * Register the widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->content_controls();
		$this->style_controls();
	}

	/**
	 * Register the Content tab controls.
	 */
	private function content_controls(): void {
		/* Data ------------------------------------------------------------ */
		$this->start_controls_section( 'section_data', array( 'label' => esc_html__( 'Bars', 'qaiyo-easy-charts' ) ) );

		$this->add_control(
			'source',
			array(
				'label'   => esc_html__( 'Data source', 'qaiyo-easy-charts' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'inline',
				'options' => array(
					'inline' => esc_html__( 'Enter bars here', 'qaiyo-easy-charts' ),
					'saved'  => esc_html__( 'Saved chart', 'qaiyo-easy-charts' ),
				),
			)
		);

		$charts = array( '0' => esc_html__( '— Select a chart —', 'qaiyo-easy-charts' ) );
		foreach ( Repository::list() as $chart ) {
			$charts[ (string) $chart['id'] ] = $chart['title'];
		}

		$this->add_control(
			'chart_id',
			array(
				'label'     => esc_html__( 'Chart', 'qaiyo-easy-charts' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '0',
				'options'   => $charts,
				'condition' => array( 'source' => 'saved' ),
			)
		);

		$repeater = new Repeater();
		$repeater->add_control(
			'label',
			array(
				'label'       => esc_html__( 'Label', 'qaiyo-easy-charts' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'value',
			array(
				'label'   => esc_html__( 'Value', 'qaiyo-easy-charts' ),
				'type'    => Controls_Manager::NUMBER,
				'dynamic' => array( 'active' => true ),
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
			'text',
			array(
				'label'       => esc_html__( 'Value text', 'qaiyo-easy-charts' ),
				'description' => esc_html__( 'Optional. Replaces the automatic text, e.g. "up to 5".', 'qaiyo-easy-charts' ),
				'type'        => Controls_Manager::TEXT,
				'dynamic'     => array( 'active' => true ),
			)
		);
		$repeater->add_control(
			'color',
			array(
				'label'     => esc_html__( 'Bar color', 'qaiyo-easy-charts' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
			)
		);
		$repeater->add_control(
			'range_color',
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
				'default'     => ChartConfig::demo_items(),
				'title_field' => '{{{ label }}}',
				'condition'   => array( 'source' => 'inline' ),
			)
		);

		$this->end_controls_section();

		/* Layout ---------------------------------------------------------- */
		$this->start_controls_section(
			'section_layout',
			array(
				'label'     => esc_html__( 'Layout', 'qaiyo-easy-charts' ),
				'condition' => array( 'source' => 'inline' ),
			)
		);

		$this->add_control(
			'orientation',
			array(
				'label'   => esc_html__( 'Orientation', 'qaiyo-easy-charts' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'horizontal',
				'options' => array(
					'horizontal' => esc_html__( 'Horizontal bars', 'qaiyo-easy-charts' ),
					'vertical'   => esc_html__( 'Vertical columns', 'qaiyo-easy-charts' ),
					'auto'       => esc_html__( 'Columns, bars on narrow screens', 'qaiyo-easy-charts' ),
				),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'     => esc_html__( 'Labels of horizontal bars', 'qaiyo-easy-charts' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'auto',
				'options'   => array(
					'auto'    => esc_html__( 'Auto (stack when narrow)', 'qaiyo-easy-charts' ),
					'inline'  => esc_html__( 'Always in one row', 'qaiyo-easy-charts' ),
					'stacked' => esc_html__( 'Always above the bar', 'qaiyo-easy-charts' ),
				),
				'condition' => array( 'orientation!' => 'vertical' ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'qaiyo-easy-charts' ),
				'description' => esc_html__( 'Used as the accessible name of the chart.', 'qaiyo-easy-charts' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'description',
			array(
				'label'       => esc_html__( 'Description', 'qaiyo-easy-charts' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => array( 'active' => true ),
			)
		);

		$this->add_control(
			'show_caption',
			array(
				'label'        => esc_html__( 'Show title and description above the chart', 'qaiyo-easy-charts' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		/* Scale ----------------------------------------------------------- */
		$this->start_controls_section(
			'section_scale',
			array(
				'label'     => esc_html__( 'Scale & axis', 'qaiyo-easy-charts' ),
				'condition' => array( 'source' => 'inline' ),
			)
		);

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
				'label'   => esc_html__( 'Unit', 'qaiyo-easy-charts' ),
				'type'    => Controls_Manager::TEXT,
				'default' => 'munkanap',
			)
		);
		$this->add_control(
			'unit_position',
			array(
				'label'   => esc_html__( 'Show unit on', 'qaiyo-easy-charts' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'last',
				'options' => array(
					'last' => esc_html__( 'Last tick', 'qaiyo-easy-charts' ),
					'all'  => esc_html__( 'Every tick', 'qaiyo-easy-charts' ),
					'none' => esc_html__( 'Hidden', 'qaiyo-easy-charts' ),
				),
			)
		);

		$this->end_controls_section();

		/* Values & behaviour ---------------------------------------------- */
		$this->start_controls_section(
			'section_values',
			array(
				'label'     => esc_html__( 'Values & behaviour', 'qaiyo-easy-charts' ),
				'condition' => array( 'source' => 'inline' ),
			)
		);

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
			'separator',
			array(
				'label'   => esc_html__( 'Range separator', 'qaiyo-easy-charts' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '–',
			)
		);
		$this->add_control(
			'prefix',
			array(
				'label' => esc_html__( 'Prefix', 'qaiyo-easy-charts' ),
				'type'  => Controls_Manager::TEXT,
			)
		);
		$this->add_control(
			'suffix',
			array(
				'label' => esc_html__( 'Suffix', 'qaiyo-easy-charts' ),
				'type'  => Controls_Manager::TEXT,
			)
		);
		$this->add_control(
			'animate',
			array(
				'label'        => esc_html__( 'Animate when scrolled into view', 'qaiyo-easy-charts' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'separator'    => 'before',
			)
		);
		$this->add_control(
			'animation',
			array(
				'label'     => esc_html__( 'Effect', 'qaiyo-easy-charts' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'grow',
				'options'   => array(
					'grow' => esc_html__( 'Grow from the axis', 'qaiyo-easy-charts' ),
					'fade' => esc_html__( 'Fade in', 'qaiyo-easy-charts' ),
					'rise' => esc_html__( 'Rise into place', 'qaiyo-easy-charts' ),
				),
				'condition' => array( 'animate' => 'yes' ),
			)
		);
		$this->add_control(
			'easing',
			array(
				'label'     => esc_html__( 'Motion curve', 'qaiyo-easy-charts' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'smooth',
				'options'   => array(
					'smooth' => esc_html__( 'Smooth', 'qaiyo-easy-charts' ),
					'linear' => esc_html__( 'Even', 'qaiyo-easy-charts' ),
					'spring' => esc_html__( 'Springy', 'qaiyo-easy-charts' ),
				),
				'condition' => array( 'animate' => 'yes' ),
			)
		);
		$this->add_control(
			'count_up',
			array(
				'label'        => esc_html__( 'Count the values up', 'qaiyo-easy-charts' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'condition'    => array( 'animate' => 'yes' ),
			)
		);
		$this->add_control(
			'replay',
			array(
				'label'        => esc_html__( 'Replay every time the chart scrolls into view', 'qaiyo-easy-charts' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'condition'    => array( 'animate' => 'yes' ),
			)
		);
		$this->add_control(
			'lazy',
			array(
				'label'        => esc_html__( 'Lazy loading: draw the chart when it scrolls near', 'qaiyo-easy-charts' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
				'separator'    => 'before',
			)
		);
		$this->add_control(
			'hover_focus',
			array(
				'label'        => esc_html__( 'Highlight the hovered bar', 'qaiyo-easy-charts' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);
		$this->add_control(
			'data_table',
			array(
				'label'        => esc_html__( 'Add an accessible data table (screen readers, SEO, AI)', 'qaiyo-easy-charts' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register the Style tab controls.
	 */
	private function style_controls(): void {
		/* Chart ----------------------------------------------------------- */
		$this->start_controls_section(
			'section_style_chart',
			array(
				'label' => esc_html__( 'Chart', 'qaiyo-easy-charts' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'chart_typography',
				'label'    => esc_html__( 'Font', 'qaiyo-easy-charts' ),
				'selector' => self::ROOT,
			)
		);

		$this->color( 'bar_color', esc_html__( 'Bar color', 'qaiyo-easy-charts' ), '--qec-primary' );
		$this->color( 'range_color', esc_html__( 'Range color', 'qaiyo-easy-charts' ), '--qec-secondary' );
		$this->color( 'track_color', esc_html__( 'Track color', 'qaiyo-easy-charts' ), '--qec-track' );

		$this->size( 'bar_size', esc_html__( 'Bar thickness', 'qaiyo-easy-charts' ), '--qec-bar-size', 4, 160, 'before' );
		$this->size( 'chart_height', esc_html__( 'Column chart height', 'qaiyo-easy-charts' ), '--qec-chart-height', 80, 800 );
		$this->size( 'bar_radius', esc_html__( 'Corner radius', 'qaiyo-easy-charts' ), '--qec-bar-radius', 0, 60 );
		$this->size( 'row_gap', esc_html__( 'Space between bars', 'qaiyo-easy-charts' ), '--qec-row-gap', 0, 120 );
		$this->size( 'column_gap', esc_html__( 'Space between columns', 'qaiyo-easy-charts' ), '--qec-column-gap', 0, 120 );

		$this->end_controls_section();

		/* Texts ----------------------------------------------------------- */
		foreach (
			array(
				'label' => array( esc_html__( 'Labels', 'qaiyo-easy-charts' ), '.qec-label', '--qec-label-color' ),
				'value' => array( esc_html__( 'Values', 'qaiyo-easy-charts' ), '.qec-value', '--qec-value-color' ),
				'axis'  => array( esc_html__( 'Axis', 'qaiyo-easy-charts' ), '.qec-axis, {{WRAPPER}} .qec-axis-title', '--qec-axis-color' ),
				'title' => array( esc_html__( 'Title', 'qaiyo-easy-charts' ), '.qec-title', '' ),
			) as $key => $spec
		) {
			$this->start_controls_section(
				'section_style_' . $key,
				array(
					'label' => $spec[0],
					'tab'   => Controls_Manager::TAB_STYLE,
				)
			);
			if ( '' !== $spec[2] ) {
				$this->color( $key . '_color', esc_html__( 'Color', 'qaiyo-easy-charts' ), $spec[2] );
			}
			$this->add_group_control(
				Group_Control_Typography::get_type(),
				array(
					'name'     => $key . '_typography',
					'selector' => self::ROOT . ' ' . $spec[1],
				)
			);
			if ( 'label' === $key ) {
				$this->size( 'label_width', esc_html__( 'Label column width', 'qaiyo-easy-charts' ), '--qec-label-width', 0, 600, 'default', array( 'px', '%', 'em' ) );
				$this->size( 'label_gap', esc_html__( 'Space before bar', 'qaiyo-easy-charts' ), '--qec-label-gap', 0, 120 );
			}
			if ( 'value' === $key ) {
				$this->size( 'value_gap', esc_html__( 'Space after bar', 'qaiyo-easy-charts' ), '--qec-value-gap', 0, 120 );
			}
			if ( 'axis' === $key ) {
				$this->size( 'axis_gap', esc_html__( 'Space above axis', 'qaiyo-easy-charts' ), '--qec-axis-gap', 0, 120 );
			}
			$this->end_controls_section();
		}

		/* Animation ------------------------------------------------------- */
		$this->start_controls_section(
			'section_style_animation',
			array(
				'label' => esc_html__( 'Animation', 'qaiyo-easy-charts' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'duration',
			array(
				'label'      => esc_html__( 'Duration (ms)', 'qaiyo-easy-charts' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array(
					'ms' => array(
						'min'  => 200,
						'max'  => 4000,
						'step' => 50,
					),
				),
				'selectors'  => array( self::ROOT => '--qec-duration: {{SIZE}}ms;' ),
			)
		);
		$this->add_control(
			'stagger',
			array(
				'label'      => esc_html__( 'Delay between bars (ms)', 'qaiyo-easy-charts' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'ms' ),
				'range'      => array(
					'ms' => array(
						'min'  => 0,
						'max'  => 1000,
						'step' => 10,
					),
				),
				'selectors'  => array( self::ROOT => '--qec-stagger: {{SIZE}}ms;' ),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Add a colour control bound to a CSS custom property.
	 *
	 * @param string $id Id.
	 * @param string $label Label.
	 * @param string $variable Variable.
	 */
	private function color( string $id, string $label, string $variable ): void {
		$this->add_control(
			$id,
			array(
				'label'     => $label,
				'type'      => Controls_Manager::COLOR,
				'default'   => ChartConfig::DEFAULT_STYLE[ $id ] ?? '',
				'selectors' => array( self::ROOT => $variable . ': {{VALUE}};' ),
			)
		);
	}

	/**
	 * Add a size control bound to a CSS custom property.
	 *
	 * @param string   $id        Control ID.
	 * @param string   $label     Control label.
	 * @param string   $variable  CSS custom property.
	 * @param int      $min       Minimum in px.
	 * @param int      $max       Maximum in px.
	 * @param string   $separator Elementor separator position.
	 * @param string[] $units     Size units.
	 */
	private function size( string $id, string $label, string $variable, int $min, int $max, string $separator = 'default', array $units = array( 'px', 'em' ) ): void {
		$range = array();
		foreach ( $units as $unit ) {
			$range[ $unit ] = 'px' === $unit
				? array(
					'min' => $min,
					'max' => $max,
				)
				: array(
					'min'  => 0,
					'max'  => 'em' === $unit ? 20 : 100,
					'step' => 'em' === $unit ? 0.1 : 1,
				);
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

	/**
	 * Settings → shared chart configuration.
	 *
	 * @return QecConfig|null
	 */
	private function config(): ?array {
		$s = $this->get_settings_for_display();

		if ( 'saved' === ( $s['source'] ?? 'inline' ) ) {
			$config = Repository::find( Cast::int( $s['chart_id'] ?? 0 ) );
			if ( null === $config ) {
				return null;
			}
			// The saved chart keeps its own look, except where the Style tab was changed:
			// inline variables on the root would otherwise override the Style tab.
			$overrides = array(
				'bar_color'                    => 'bar_color',
				'range_color'                  => 'range_color',
				'track_color'                  => 'track_color',
				'label_color'                  => 'label_color',
				'value_color'                  => 'value_color',
				'axis_color'                   => 'axis_color',
				'chart_typography_font_family' => 'font_family',
				'bar_size'                     => 'bar_size',
				'chart_height'                 => 'chart_height',
				'bar_radius'                   => 'radius',
			);
			foreach ( $overrides as $control => $key ) {
				$value   = $s[ $control ] ?? '';
				$default = ChartConfig::DEFAULT_STYLE[ $control ] ?? '';
				if ( ( is_array( $value ) && '' !== Cast::string( $value['size'] ?? '' ) ) || ( is_string( $value ) && '' !== $value && strtolower( $value ) !== $default ) ) {
					$config[ $key ] = '';
				}
			}
			return $config;
		}

		$yes = static function ( $value ): bool {
			return 'yes' === $value;
		};

		return ChartConfig::sanitize(
			array(
				'items'         => Cast::arr( $s['items'] ?? array() ),
				'orientation'   => $s['orientation'] ?? 'horizontal',
				'layout'        => $s['layout'] ?? 'auto',
				'title'         => $s['title'] ?? '',
				'description'   => $s['description'] ?? '',
				'show_caption'  => $yes( $s['show_caption'] ?? '' ),
				'scale_min'     => $s['scale_min'] ?? 0,
				'scale_max'     => $s['scale_max'] ?? '',
				'step'          => $s['step'] ?? '',
				'show_axis'     => $yes( $s['show_axis'] ?? '' ),
				'unit'          => $s['unit'] ?? '',
				'unit_position' => $s['unit_position'] ?? 'last',
				'show_values'   => $yes( $s['show_values'] ?? '' ),
				'separator'     => $s['separator'] ?? '–',
				'prefix'        => $s['prefix'] ?? '',
				'suffix'        => $s['suffix'] ?? '',
				'animate'       => $yes( $s['animate'] ?? '' ),
				'animation'     => $s['animation'] ?? 'grow',
				'easing'        => $s['easing'] ?? 'smooth',
				'count_up'      => $yes( $s['count_up'] ?? '' ),
				'replay'        => $yes( $s['replay'] ?? '' ),
				'lazy'          => $yes( $s['lazy'] ?? '' ),
				'hover_focus'   => $yes( $s['hover_focus'] ?? '' ),
				'data_table'    => $yes( $s['data_table'] ?? '' ),
			)
		);
	}

	/**
	 * Render the output.
	 *
	 * @return void
	 */
	protected function render() {
		$config = $this->config();
		if ( null === $config || array() === $config['items'] ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'Select a saved chart or add at least one bar.', 'qaiyo-easy-charts' ) . '</p>';
			}
			return;
		}

		// In the Elementor editor the chart is drawn at once.
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			$config['lazy'] = false;
		}

		echo Renderer::render( $config ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the renderer.
	}

	/**
	 * Meaningful plain text for search, feeds and when Elementor is deactivated.
	 *
	 * @return void
	 */
	public function render_plain_content() {
		$config = $this->config();
		if ( null !== $config ) {
			echo esc_html( Renderer::plain_text( $config ) );
		}
	}
}
