<?php
/**
 * Bricks Builder integration: registers the element when Bricks is active.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Integrations\Bricks;

use Qaiyo\EasyCharts\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the chart element with Bricks Builder.
 */
final class BricksIntegration {

	/**
	 * Hook this component into WordPress.
	 */
	public function register(): void {
		// Bricks (a theme) loads before init; elements must be registered after priority 10.
		add_action( 'init', array( $this, 'element' ), 11 );
		add_filter( 'bricks/builder/i18n', array( $this, 'i18n' ) );
	}

	/**
	 * Register the element with Bricks.
	 */
	public function element(): void {
		if ( ! class_exists( '\Bricks\Elements' ) ) {
			return;
		}
		\Bricks\Elements::register_element(
			QAIYO_EC_PLUGIN_DIR . 'src/Integrations/Bricks/ChartElement.php',
			Schema::ELEMENT,
			ChartElement::class
		);
	}

	/**
	 * Label of the "qaiyo" element category in the Bricks panel.
	 *
	 * @param mixed $i18n Builder strings.
	 * @return mixed
	 */
	public function i18n( $i18n ) {
		if ( is_array( $i18n ) ) {
			$i18n['qaiyo'] = esc_html__( 'Qaiyo', 'qaiyo-easy-charts' );
		}
		return $i18n;
	}
}
