<?php
/**
 * Gutenberg block: qaiyo-easy-charts/chart (dynamic, server-rendered).
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Blocks;

use Qaiyo\EasyCharts\Admin\PostType;
use Qaiyo\EasyCharts\Chart\ChartConfig;
use Qaiyo\EasyCharts\Chart\Renderer;
use Qaiyo\EasyCharts\Chart\Repository;
use Qaiyo\EasyCharts\Frontend\Assets;
use Qaiyo\EasyCharts\Schema;
use Qaiyo\EasyCharts\Support\Cast;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders the Gutenberg block.
 */
final class ChartBlock {

	/** Block attribute => configuration key (inline source). */
	const ATTRIBUTE_MAP = array(
		'orientation'  => 'orientation',
		'layout'       => 'layout',
		'title'        => 'title',
		'description'  => 'description',
		'showCaption'  => 'show_caption',
		'unit'         => 'unit',
		'unitPosition' => 'unit_position',
		'scaleMax'     => 'scale_max',
		'step'         => 'step',
		'showAxis'     => 'show_axis',
		'showValues'   => 'show_values',
		'animate'      => 'animate',
		'animation'    => 'animation',
		'easing'       => 'easing',
		'duration'     => 'duration',
		'stagger'      => 'stagger',
		'countUp'      => 'count_up',
		'replay'       => 'replay',
		'lazy'         => 'lazy',
		'hoverFocus'   => 'hover_focus',
	);

	/** Style attributes, applied on top of saved charts too. */
	const STYLE_MAP = array(
		'barColor'    => 'bar_color',
		'rangeColor'  => 'range_color',
		'trackColor'  => 'track_color',
		'labelColor'  => 'label_color',
		'valueColor'  => 'value_color',
		'axisColor'   => 'axis_color',
		'barSize'     => 'bar_size',
		'chartHeight' => 'chart_height',
		'radius'      => 'radius',
	);

	/**
	 * Hook this component into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_data' ) );
	}

	/**
	 * Register the block and its editor script.
	 */
	public function register_block(): void {
		Assets::register_handles();

		wp_register_script(
			Schema::HANDLE_BLOCK,
			QAIYO_EC_PLUGIN_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render' ),
			Assets::version( 'assets/js/block.js' ),
			array( 'in_footer' => true )
		);

		register_block_type(
			QAIYO_EC_PLUGIN_DIR . 'blocks/chart',
			array( 'render_callback' => array( self::class, 'render' ) )
		);
	}

	/**
	 * Chart list and UI strings for the editor (only when the editor loads).
	 */
	public function editor_data(): void {
		$charts = array_map(
			static function ( array $chart ): array {
				return array(
					'value' => $chart['id'],
					'label' => 'publish' === $chart['status'] ? $chart['title'] : $chart['title'] . ' (' . $chart['status'] . ')',
				);
			},
			current_user_can( 'edit_posts' ) ? Repository::list() : array()
		);

		wp_add_inline_script(
			Schema::HANDLE_BLOCK,
			'window.qaiyoEcBlock = ' . wp_json_encode(
				array(
					'charts'    => $charts,
					'defaults'  => ChartConfig::DEFAULT_STYLE,
					'manageUrl' => admin_url( PostType::MENU_SLUG ),
					'i18n'      => self::strings(),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * UI strings for the block editor.
	 *
	 * @return array<string,string>
	 */
	private static function strings(): array {
		return array(
			'source'        => __( 'Data source', 'qaiyo-easy-charts' ),
			'inline'        => __( 'Enter bars here', 'qaiyo-easy-charts' ),
			'saved'         => __( 'Saved chart', 'qaiyo-easy-charts' ),
			'chart'         => __( 'Chart', 'qaiyo-easy-charts' ),
			'selectChart'   => __( '— Select a chart —', 'qaiyo-easy-charts' ),
			'manage'        => __( 'Manage charts', 'qaiyo-easy-charts' ),
			'noChart'       => __( 'Select a saved chart in the block settings.', 'qaiyo-easy-charts' ),
			'bars'          => __( 'Bars', 'qaiyo-easy-charts' ),
			'bar'           => __( 'Bar', 'qaiyo-easy-charts' ),
			'label'         => __( 'Label', 'qaiyo-easy-charts' ),
			'value'         => __( 'Value', 'qaiyo-easy-charts' ),
			'rangeEnd'      => __( 'Range end', 'qaiyo-easy-charts' ),
			'valueText'     => __( 'Value text', 'qaiyo-easy-charts' ),
			'addBar'        => __( 'Add bar', 'qaiyo-easy-charts' ),
			'removeBar'     => __( 'Remove bar', 'qaiyo-easy-charts' ),
			'moveUp'        => __( 'Move bar up', 'qaiyo-easy-charts' ),
			'moveDown'      => __( 'Move bar down', 'qaiyo-easy-charts' ),
			'layoutPanel'   => __( 'Layout', 'qaiyo-easy-charts' ),
			'orientation'   => __( 'Orientation', 'qaiyo-easy-charts' ),
			'default'       => __( 'Default (from settings)', 'qaiyo-easy-charts' ),
			'horizontal'    => __( 'Horizontal bars', 'qaiyo-easy-charts' ),
			'vertical'      => __( 'Vertical columns', 'qaiyo-easy-charts' ),
			'auto'          => __( 'Columns, bars on narrow screens', 'qaiyo-easy-charts' ),
			'labels'        => __( 'Labels of horizontal bars', 'qaiyo-easy-charts' ),
			'layoutAuto'    => __( 'Auto (stack when narrow)', 'qaiyo-easy-charts' ),
			'layoutInline'  => __( 'Always in one row', 'qaiyo-easy-charts' ),
			'layoutStacked' => __( 'Always above the bar', 'qaiyo-easy-charts' ),
			'title'         => __( 'Title', 'qaiyo-easy-charts' ),
			'description'   => __( 'Description', 'qaiyo-easy-charts' ),
			'showCaption'   => __( 'Show title and description above the chart', 'qaiyo-easy-charts' ),
			'scalePanel'    => __( 'Scale & axis', 'qaiyo-easy-charts' ),
			'unit'          => __( 'Unit', 'qaiyo-easy-charts' ),
			'unitPosition'  => __( 'Show unit on', 'qaiyo-easy-charts' ),
			'unitLast'      => __( 'Last tick', 'qaiyo-easy-charts' ),
			'unitAll'       => __( 'Every tick', 'qaiyo-easy-charts' ),
			'unitNone'      => __( 'Hidden', 'qaiyo-easy-charts' ),
			'maximum'       => __( 'Maximum', 'qaiyo-easy-charts' ),
			'maximumHelp'   => __( 'Leave empty to calculate from the data.', 'qaiyo-easy-charts' ),
			'step'          => __( 'Axis step', 'qaiyo-easy-charts' ),
			'showAxis'      => __( 'Show axis', 'qaiyo-easy-charts' ),
			'showValues'    => __( 'Show values', 'qaiyo-easy-charts' ),
			'colors'        => __( 'Colors', 'qaiyo-easy-charts' ),
			'barColor'      => __( 'Bar color', 'qaiyo-easy-charts' ),
			'rangeColor'    => __( 'Range color', 'qaiyo-easy-charts' ),
			'trackColor'    => __( 'Track color', 'qaiyo-easy-charts' ),
			'labelColor'    => __( 'Label color', 'qaiyo-easy-charts' ),
			'valueColor'    => __( 'Value color', 'qaiyo-easy-charts' ),
			'axisColor'     => __( 'Axis color', 'qaiyo-easy-charts' ),
			'size'          => __( 'Size', 'qaiyo-easy-charts' ),
			'barSize'       => __( 'Bar thickness', 'qaiyo-easy-charts' ),
			'chartHeight'   => __( 'Column chart height', 'qaiyo-easy-charts' ),
			'radius'        => __( 'Corner radius', 'qaiyo-easy-charts' ),
			'behaviour'     => __( 'Behaviour', 'qaiyo-easy-charts' ),
			'animate'       => __( 'Animate when scrolled into view', 'qaiyo-easy-charts' ),
			'hoverFocus'    => __( 'Highlight the hovered bar', 'qaiyo-easy-charts' ),
			'animation'     => __( 'Animation', 'qaiyo-easy-charts' ),
			'effect'        => __( 'Effect', 'qaiyo-easy-charts' ),
			'grow'          => __( 'Grow from the axis', 'qaiyo-easy-charts' ),
			'fade'          => __( 'Fade in', 'qaiyo-easy-charts' ),
			'rise'          => __( 'Rise into place', 'qaiyo-easy-charts' ),
			'easing'        => __( 'Motion curve', 'qaiyo-easy-charts' ),
			'smooth'        => __( 'Smooth', 'qaiyo-easy-charts' ),
			'linear'        => __( 'Even', 'qaiyo-easy-charts' ),
			'spring'        => __( 'Springy', 'qaiyo-easy-charts' ),
			'duration'      => __( 'Duration (ms)', 'qaiyo-easy-charts' ),
			'stagger'       => __( 'Delay between bars (ms)', 'qaiyo-easy-charts' ),
			'countUp'       => __( 'Count the values up', 'qaiyo-easy-charts' ),
			'replay'        => __( 'Replay every time the chart scrolls into view', 'qaiyo-easy-charts' ),
			'lazy'          => __( 'Lazy loading: draw the chart when it scrolls near', 'qaiyo-easy-charts' ),
		);
	}

	/**
	 * Render the output.
	 *
	 * @param mixed $attributes Block attributes.
	 */
	public static function render( $attributes ): string {
		$attributes = Cast::arr( $attributes );

		if ( 'saved' === ( $attributes['source'] ?? '' ) ) {
			$config = Repository::find( Cast::int( $attributes['chartId'] ?? 0 ) );
			if ( null === $config ) {
				return '';
			}
		} else {
			$config          = ChartConfig::defaults();
			$config['items'] = Cast::arr( $attributes['items'] ?? array() );
			foreach ( self::ATTRIBUTE_MAP as $attribute => $key ) {
				if ( array_key_exists( $attribute, $attributes ) && '' !== $attributes[ $attribute ] ) {
					$config[ $key ] = $attributes[ $attribute ];
				}
			}
		}

		foreach ( self::STYLE_MAP as $attribute => $key ) {
			if ( ! empty( $attributes[ $attribute ] ) ) {
				$config[ $key ] = $attributes[ $attribute ];
			}
		}

		// Block typography: the chosen preset font wins over the global chart font.
		if ( ! empty( $attributes['fontFamily'] ) ) {
			$config['font_family'] = 'var(--wp--preset--font-family--' . sanitize_key( Cast::string( $attributes['fontFamily'] ) ) . ')';
		}

		$config = ChartConfig::sanitize( $config );
		if ( array() === $config['items'] ) {
			return '';
		}

		// The editor's server-side preview has no front-end script: draw it at once.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$config['lazy'] = false;
		}

		return sprintf(
			'<div %s>%s</div>',
			get_block_wrapper_attributes(),
			Renderer::render( $config )
		);
	}
}
