<?php
/**
 * Plugin Name:       Qaiyo Easy Charts
 * Plugin URI:        https://qaiyo-plugins.com/qaiyo-easy-charts/
 * Description:       Simple, beautiful, animated and accessible charts. Build them in the admin and place them with a shortcode, a Gutenberg block, Elementor or Bricks Builder.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Author:            Qaiyo
 * Author URI:        https://qaiyo-plugins.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       qaiyo-easy-charts
 * Domain Path:       /languages
 *
 * @package qaiyo-easy-charts
 */

defined( 'ABSPATH' ) || exit;

define( 'QAIYO_EC_VERSION', '1.0.0' );
define( 'QAIYO_EC_PLUGIN_FILE', __FILE__ );
define( 'QAIYO_EC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QAIYO_EC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QAIYO_EC_BASENAME', plugin_basename( __FILE__ ) );
define( 'QAIYO_EC_MIN_PHP', '8.0' );
define( 'QAIYO_EC_MIN_WP', '6.2' );

/*
 * PSR-4 autoloader for the plugin's own namespace, mapped to src/.
 * Composer is optional: the plugin has no third-party dependencies.
 */
spl_autoload_register(
	static function ( $class_name ) {
		$prefix = 'Qaiyo\\EasyCharts\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}
		$file = QAIYO_EC_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

/**
 * Requirement problems, in plain English (runs before translations may load).
 *
 * @return string[]
 */
function qaiyo_ec_requirement_errors() {
	$errors = array();
	if ( version_compare( PHP_VERSION, QAIYO_EC_MIN_PHP, '<' ) ) {
		$errors[] = sprintf( 'Qaiyo Easy Charts requires PHP %1$s or higher. This site runs PHP %2$s.', QAIYO_EC_MIN_PHP, PHP_VERSION );
	}
	if ( version_compare( get_bloginfo( 'version' ), QAIYO_EC_MIN_WP, '<' ) ) {
		$errors[] = sprintf( 'Qaiyo Easy Charts requires WordPress %s or higher.', QAIYO_EC_MIN_WP );
	}
	return $errors;
}

register_activation_hook(
	__FILE__,
	static function () {
		$errors = qaiyo_ec_requirement_errors();
		if ( array() !== $errors ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			deactivate_plugins( QAIYO_EC_BASENAME );
			wp_die( esc_html( implode( ' ', $errors ) ), 'Qaiyo Easy Charts', array( 'back_link' => true ) );
		}
		\Qaiyo\EasyCharts\Plugin::activate();
	}
);

/**
 * Load translations following the Qaiyo i18n standard: bundled .mo files for
 * the supported locales, regional variants mapped to their base language.
 *
 * @return void
 */
function qaiyo_ec_load_translations() {
	$locale = determine_locale();

	$locale_map = array(
		'de_CH'          => 'de_DE',
		'de_AT'          => 'de_DE',
		'de_DE_formal'   => 'de_DE',
		'de_CH_informal' => 'de_DE',
		'fr_BE'          => 'fr_FR',
		'fr_CA'          => 'fr_FR',
		'fr_CH'          => 'fr_FR',
		'es_MX'          => 'es_ES',
		'es_AR'          => 'es_ES',
		'es_CO'          => 'es_ES',
		'es_VE'          => 'es_ES',
		'es_CL'          => 'es_ES',
		'es_PE'          => 'es_ES',
		'pt_BR'          => 'pt_PT',
		'pt_AO'          => 'pt_PT',
		'pt_MZ'          => 'pt_PT',
		'ja_JP'          => 'ja',
		'ru_UA'          => 'ru_RU',
		'ru_BY'          => 'ru_RU',
		'tr_CY'          => 'tr_TR',
	);

	if ( isset( $locale_map[ $locale ] ) ) {
		$locale = $locale_map[ $locale ];
	}

	$supported = array( 'hu_HU', 'de_DE', 'fr_FR', 'es_ES', 'ja', 'pt_PT', 'it_IT', 'ru_RU', 'tr_TR', 'pl_PL' );
	if ( ! in_array( $locale, $supported, true ) ) {
		return;
	}

	$mofile = QAIYO_EC_PLUGIN_DIR . 'languages/qaiyo-easy-charts-' . $locale . '.mo';
	if ( is_readable( $mofile ) ) {
		load_textdomain( 'qaiyo-easy-charts', $mofile );
	}
}
add_action( 'init', 'qaiyo_ec_load_translations', 1 );

add_action(
	'plugins_loaded',
	static function () {
		if ( array() !== qaiyo_ec_requirement_errors() ) {
			return;
		}
		( new \Qaiyo\EasyCharts\Plugin() )->register();
	}
);
