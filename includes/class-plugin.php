<?php
/**
 * Plugin bootstrap: assets and builder integrations.
 *
 * @package QaiyoEasyCharts
 */

namespace Qaiyo\EasyCharts;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	const STYLE_HANDLE  = 'qaiyo-easy-charts';
	const SCRIPT_HANDLE = 'qaiyo-easy-charts';

	/** @var Plugin|null */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		require_once QAIYO_EASY_CHARTS_PATH . 'includes/class-helpers.php';
		require_once QAIYO_EASY_CHARTS_PATH . 'includes/charts/class-range-bars.php';

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'register_assets' ), 5 );

		// Elementor.
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_elementor_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widgets' ) );

		// Bricks (theme loads before `init`; elements must be registered after priority 10).
		add_action( 'init', array( $this, 'register_bricks_elements' ), 11 );
		add_filter( 'bricks/builder/i18n', array( $this, 'bricks_i18n' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'qaiyo-easy-charts', false, dirname( plugin_basename( QAIYO_EASY_CHARTS_FILE ) ) . '/languages' );
	}

	public function register_assets() {
		wp_register_style(
			self::STYLE_HANDLE,
			QAIYO_EASY_CHARTS_URL . 'assets/css/easy-charts.css',
			array(),
			QAIYO_EASY_CHARTS_VERSION
		);

		wp_register_script(
			self::SCRIPT_HANDLE,
			QAIYO_EASY_CHARTS_URL . 'assets/js/easy-charts.js',
			array(),
			QAIYO_EASY_CHARTS_VERSION,
			true
		);
	}

	public static function enqueue_assets() {
		wp_enqueue_style( self::STYLE_HANDLE );
		wp_enqueue_script( self::SCRIPT_HANDLE );
	}

	/**
	 * @param \Elementor\Elements_Manager $elements_manager
	 */
	public function register_elementor_category( $elements_manager ) {
		$elements_manager->add_category(
			'qaiyo',
			array(
				'title' => esc_html__( 'Qaiyo', 'qaiyo-easy-charts' ),
				'icon'  => 'eicon-favorite',
			)
		);
	}

	/**
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_elementor_widgets( $widgets_manager ) {
		require_once QAIYO_EASY_CHARTS_PATH . 'includes/integrations/elementor/class-range-bars-widget.php';
		$widgets_manager->register( new Integrations\Elementor\Range_Bars_Widget() );
	}

	public function register_bricks_elements() {
		if ( ! class_exists( '\Bricks\Elements' ) ) {
			return;
		}
		\Bricks\Elements::register_element(
			QAIYO_EASY_CHARTS_PATH . 'includes/integrations/bricks/class-range-bars-element.php',
			'qaiyo-range-bars',
			'Qaiyo\\EasyCharts\\Integrations\\Bricks\\Range_Bars_Element'
		);
	}

	/**
	 * Label for the "qaiyo" element category in the Bricks panel.
	 */
	public function bricks_i18n( $i18n ) {
		$i18n['qaiyo'] = esc_html__( 'Qaiyo', 'qaiyo-easy-charts' );
		return $i18n;
	}
}
