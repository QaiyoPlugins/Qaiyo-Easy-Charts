<?php
/**
 * Elementor integration: category + widget, only when Elementor is loaded.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Integrations\Elementor;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Qaiyo category and the chart widget with Elementor.
 */
final class ElementorIntegration {

	/** Oldest Elementor with the elementor/widgets/register contract. */
	const MIN_VERSION = '3.5.0';

	/**
	 * Hook this component into WordPress.
	 */
	public function register(): void {
		add_action( 'elementor/loaded', array( $this, 'boot' ) );
		if ( did_action( 'elementor/loaded' ) ) {
			$this->boot();
		}
	}

	/**
	 * Register the Elementor hooks once Elementor is loaded and compatible.
	 */
	public function boot(): void {
		static $booted = false;
		if ( $booted || ! defined( 'ELEMENTOR_VERSION' ) || version_compare( ELEMENTOR_VERSION, self::MIN_VERSION, '<' ) ) {
			return;
		}
		$booted = true;

		add_action( 'elementor/elements/categories_registered', array( $this, 'category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'widgets' ) );
	}

	/**
	 * Register the "Qaiyo" widget category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elements manager.
	 */
	public function category( $elements_manager ): void {
		$elements_manager->add_category(
			'qaiyo',
			array(
				'title' => esc_html__( 'Qaiyo', 'qaiyo-easy-charts' ),
				'icon'  => 'eicon-favorite',
			)
		);
	}

	/**
	 * Register the widget.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
	 */
	public function widgets( $widgets_manager ): void {
		$widgets_manager->register( new ChartWidget() );
	}
}
