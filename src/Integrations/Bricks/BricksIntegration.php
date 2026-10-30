<?php
/**
 * Bricks Builder integration: registers the element when Bricks 2.0 or newer
 * is active, and tells administrators to update an older Bricks.
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

	/** Oldest supported Bricks version. */
	const MIN_VERSION = '2.0';

	/**
	 * Hook this component into WordPress.
	 */
	public function register(): void {
		// Bricks (a theme) loads before init; elements must be registered after priority 10.
		add_action( 'init', array( $this, 'element' ), 11 );
		add_filter( 'bricks/builder/i18n', array( $this, 'i18n' ) );
		add_action( 'admin_notices', array( $this, 'outdated_notice' ) );
	}

	/**
	 * Whether the active Bricks is new enough for the element.
	 */
	public static function is_supported(): bool {
		return class_exists( '\Bricks\Elements' )
			&& defined( 'BRICKS_VERSION' )
			&& version_compare( (string) BRICKS_VERSION, self::MIN_VERSION, '>=' );
	}

	/**
	 * Notice for administrators running a Bricks version older than 2.0.
	 */
	public function outdated_notice(): void {
		if ( ! defined( 'BRICKS_VERSION' ) || self::is_supported() || ! current_user_can( 'update_themes' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: 1: required Bricks version, 2: installed Bricks version. */
					__( 'Qaiyo Easy Charts: the Bricks element needs Bricks %1$s or newer (installed: %2$s). Update Bricks to use it; shortcodes and blocks keep working.', 'qaiyo-easy-charts' ),
					self::MIN_VERSION,
					(string) BRICKS_VERSION
				)
			)
		);
	}

	/**
	 * Register the element with Bricks.
	 */
	public function element(): void {
		if ( ! self::is_supported() ) {
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
	public function i18n( mixed $i18n ): mixed {
		if ( is_array( $i18n ) ) {
			$i18n['qaiyo'] = esc_html__( 'Qaiyo', 'qaiyo-easy-charts' );
		}
		return $i18n;
	}
}
