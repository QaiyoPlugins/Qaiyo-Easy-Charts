<?php
/**
 * Every identifier that appears in more than one place.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts;

defined( 'ABSPATH' ) || exit;

/**
 * Central list of identifiers (post type, meta, options, handles, hooks).
 */
final class Schema {

	/** Text domain / plugin slug. */
	const SLUG = 'qaiyo-easy-charts';

	/** Saved charts. */
	const POST_TYPE = 'qaiyo_ec_chart';

	/** Post meta holding a saved chart's configuration (array). */
	const META_CONFIG = '_qaiyo_ec_config';

	/** Global settings option (array). */
	const OPTION_SETTINGS = 'qaiyo_ec_settings';

	/** Settings API group / page. */
	const SETTINGS_GROUP = 'qaiyo_ec_settings_group';
	const SETTINGS_PAGE  = 'qaiyo-ec-settings';

	/** Shortcode tag. */
	const SHORTCODE = 'qaiyo_chart';

	/** Gutenberg block name. */
	const BLOCK = 'qaiyo-easy-charts/chart';

	/** Elementor widget / Bricks element name. Stored in page data: never rename. */
	const ELEMENT = 'qaiyo-easy-chart';

	/** Asset handles. */
	const HANDLE_CHART        = 'qaiyo-ec-chart';
	const HANDLE_ADMIN        = 'qaiyo-ec-admin';
	const HANDLE_EDITOR       = 'qaiyo-ec-chart-editor';
	const HANDLE_BLOCK        = 'qaiyo-ec-block-editor';
	const HANDLE_MORE_PLUGINS = 'qaiyo-ec-more-plugins';
	const HANDLE_TYPE_PICKER  = 'qaiyo-ec-type-picker';

	/** Admin-ajax action for the live preview in the chart editor. */
	const AJAX_PREVIEW = 'qaiyo_ec_preview';

	/** Nonces. */
	const NONCE_EDITOR  = 'qaiyo_ec_save_chart';
	const NONCE_PREVIEW = 'qaiyo_ec_preview';

	/** Required capability for the settings screen. */
	const CAP_SETTINGS = 'manage_options';

	/** Abilities API category. */
	const ABILITY_CATEGORY = 'qaiyo-easy-charts';

	/** Transient used by the "Discover Qaiyo" page. */
	const TRANSIENT_MORE_PLUGINS = 'qaiyo_ec_more_plugins_wporg';

	/**
	 * Not instantiable.
	 */
	private function __construct() {}
}
