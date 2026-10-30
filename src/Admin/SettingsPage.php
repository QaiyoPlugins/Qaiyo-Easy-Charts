<?php
/**
 * Easy Charts → Settings (Settings API, posts to options.php).
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Admin;

use Qaiyo\EasyCharts\Frontend\Assets;
use Qaiyo\EasyCharts\Frontend\Shortcode;
use Qaiyo\EasyCharts\Schema;
use Qaiyo\EasyCharts\Support\Cast;

defined( 'ABSPATH' ) || exit;

final class SettingsPage {

	/** @var string */
	private static $hook = '';

	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_menu', array( $this, 'menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'update_option_' . Schema::OPTION_SETTINGS, array( Settings::class, 'flush_cache' ) );
	}

	public static function url(): string {
		return admin_url( PostType::MENU_SLUG . '&page=' . Schema::SETTINGS_PAGE );
	}

	public function register_setting(): void {
		register_setting(
			Schema::SETTINGS_GROUP,
			Schema::OPTION_SETTINGS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Settings::class, 'sanitize' ),
				'default'           => Settings::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	public function menu(): void {
		$hook = add_submenu_page(
			PostType::MENU_SLUG,
			__( 'Easy Charts settings', 'qaiyo-easy-charts' ),
			__( 'Settings', 'qaiyo-easy-charts' ),
			Schema::CAP_SETTINGS,
			Schema::SETTINGS_PAGE,
			array( $this, 'render' )
		);
		self::$hook = is_string( $hook ) ? $hook : '';
	}

	/**
	 * @param mixed $hook_suffix Current admin page.
	 */
	public function enqueue( $hook_suffix ): void {
		if ( '' === self::$hook || Cast::string( $hook_suffix ) !== self::$hook ) {
			return;
		}
		wp_enqueue_style( Schema::HANDLE_ADMIN, QEC_PLUGIN_URL . 'assets/css/admin.css', array(), Assets::version( 'assets/css/admin.css' ) );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){$(".qec-settings-wrap input.qec-color").wpColorPicker();});' );
	}

	public function render(): void {
		if ( ! current_user_can( Schema::CAP_SETTINGS ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'qaiyo-easy-charts' ) );
		}

		View::output(
			'settings',
			array(
				'qec_settings'      => Settings::get(),
				'qec_defaults'      => Settings::defaults(),
				'qec_greeting_name' => View::greeting_name(),
				'qec_attributes'    => self::shortcode_reference(),
			)
		);
	}

	/**
	 * Shortcode attribute reference shown on the settings screen.
	 *
	 * @return array<string,string>
	 */
	private static function shortcode_reference(): array {
		$reference = array(
			'id'          => __( 'ID of a saved chart. Any other attribute overrides its settings.', 'qaiyo-easy-charts' ),
			'data'        => __( 'Inline bars: rows separated by "|", fields by ";" — label;value;range end;value text.', 'qaiyo-easy-charts' ),
			'orientation' => 'horizontal | vertical | auto',
			'title'       => __( 'Title (accessible name; shown with show_title="yes").', 'qaiyo-easy-charts' ),
			'unit'        => __( 'Unit, e.g. days.', 'qaiyo-easy-charts' ),
			'min / max / step' => __( 'Axis range and step. Empty max: automatic.', 'qaiyo-easy-charts' ),
			'color / range_color / track_color' => __( 'Colors, e.g. #0f0c24.', 'qaiyo-easy-charts' ),
			'font'        => __( 'Font family, e.g. "DM Sans, sans-serif".', 'qaiyo-easy-charts' ),
			'bar_size / height / radius' => __( 'Bar thickness, column chart height, corner radius, e.g. 28px.', 'qaiyo-easy-charts' ),
			'animate / hover / table / axis / values' => 'yes | no',
		);

		// Keep the reference honest: every documented single attribute must exist.
		foreach ( array_keys( $reference ) as $attribute ) {
			if ( false === strpos( $attribute, ' ' ) && ! in_array( $attribute, array( 'id', 'data' ), true ) && ! isset( Shortcode::ATTRIBUTE_MAP[ $attribute ] ) ) {
				unset( $reference[ $attribute ] );
			}
		}
		return $reference;
	}
}
