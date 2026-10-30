<?php
/**
 * Composition root: wires every component once, on plugins_loaded.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts;

use Qaiyo\EasyCharts\Admin\BrandMenu;
use Qaiyo\EasyCharts\Admin\ChartEditor;
use Qaiyo\EasyCharts\Admin\MorePlugins;
use Qaiyo\EasyCharts\Admin\PostType;
use Qaiyo\EasyCharts\Admin\SettingsPage;
use Qaiyo\EasyCharts\Ai\Abilities;
use Qaiyo\EasyCharts\Blocks\ChartBlock;
use Qaiyo\EasyCharts\Frontend\Assets;
use Qaiyo\EasyCharts\Frontend\Shortcode;
use Qaiyo\EasyCharts\Integrations\Bricks\BricksIntegration;
use Qaiyo\EasyCharts\Integrations\Elementor\ElementorIntegration;
use Qaiyo\EasyCharts\Stats\Views;

defined( 'ABSPATH' ) || exit;

/**
 * Wires every component of the plugin.
 */
final class Plugin {

	/**
	 * Hook this component into WordPress.
	 */
	public function register(): void {
		( new PostType() )->register();
		( new Assets() )->register();
		( new Shortcode() )->register();
		( new ChartBlock() )->register();
		( new ElementorIntegration() )->register();
		( new BricksIntegration() )->register();
		( new Abilities() )->register();
		( new Views() )->register();

		if ( is_admin() ) {
			BrandMenu::init();
			( new ChartEditor() )->register();
			( new SettingsPage() )->register();
			MorePlugins::register( PostType::MENU_SLUG );
			add_filter( 'plugin_action_links_' . QAIYO_EC_BASENAME, array( $this, 'action_links' ) );
		}
	}

	/**
	 * Activation: seed settings without overwriting existing ones.
	 */
	public static function activate(): void {
		add_option( Schema::OPTION_SETTINGS, array(), '', false );
	}

	/**
	 * "Charts" and "Settings" links on the Plugins screen.
	 *
	 * @param mixed $links Existing links.
	 * @return mixed
	 */
	public function action_links( mixed $links ): mixed {
		if ( ! is_array( $links ) ) {
			return $links;
		}
		array_unshift(
			$links,
			sprintf( '<a href="%s">%s</a>', esc_url( admin_url( PostType::MENU_SLUG ) ), esc_html__( 'Charts', 'qaiyo-easy-charts' ) ),
			sprintf( '<a href="%s">%s</a>', esc_url( SettingsPage::url() ), esc_html__( 'Settings', 'qaiyo-easy-charts' ) )
		);
		return $links;
	}
}
