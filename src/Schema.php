<?php
/**
 * Every identifier that appears in more than one place.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts;

defined( 'ABSPATH' ) || exit;

final class Schema {

	/** Text domain / plugin slug. */
	const SLUG = 'qaiyo-easy-charts';

	/** Saved charts. */
	const POST_TYPE = 'qec_chart';

	/** Post meta holding a saved chart's configuration (array). */
	const META_CONFIG = '_qec_config';

	/** Global settings option (array). */
	const OPTION_SETTINGS = 'qec_settings';

	/** Settings API group / page. */
	const SETTINGS_GROUP = 'qec_settings_group';
	const SETTINGS_PAGE  = 'qec-settings';

	/** Shortcode tag. */
	const SHORTCODE = 'qaiyo_chart';

	/** Gutenberg block name. */
	const BLOCK = 'qaiyo-easy-charts/chart';

	/** Elementor widget / Bricks element name. Stored in page data: never rename. */
	const ELEMENT = 'qaiyo-easy-chart';

	/** Asset handles. */
	const HANDLE_CHART        = 'qec-chart';
	const HANDLE_ADMIN        = 'qec-admin';
	const HANDLE_EDITOR       = 'qec-chart-editor';
	const HANDLE_BLOCK        = 'qec-block-editor';
	const HANDLE_MORE_PLUGINS = 'qec-more-plugins';

	/** Admin-ajax action for the live preview in the chart editor. */
	const AJAX_PREVIEW = 'qec_preview';

	/** Nonces. */
	const NONCE_EDITOR  = 'qec_save_chart';
	const NONCE_PREVIEW = 'qec_preview';

	/** Required capability for the settings screen. */
	const CAP_SETTINGS = 'manage_options';

	/** Public filter hooks. See HOOKS.md. */
	const FILTER_DEFAULTS = 'qaiyo_easy_charts_default_config';
	const FILTER_CONFIG   = 'qaiyo_easy_charts_config';
	const FILTER_HTML     = 'qaiyo_easy_charts_chart_html';

	/** Abilities API category. */
	const ABILITY_CATEGORY = 'qaiyo-easy-charts';

	/** Transient used by the "Discover Qaiyo" page. */
	const TRANSIENT_MORE_PLUGINS = 'qec_more_plugins_wporg';

	private function __construct() {}
}
