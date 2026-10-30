<?php
/**
 * Plugin Name:       Qaiyo Easy Charts
 * Description:       Simple, beautiful, animated charts for Elementor and Bricks Builder.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Qaiyo
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       qaiyo-easy-charts
 * Domain Path:       /languages
 *
 * @package QaiyoEasyCharts
 */

defined( 'ABSPATH' ) || exit;

define( 'QAIYO_EASY_CHARTS_VERSION', '1.0.0' );
define( 'QAIYO_EASY_CHARTS_FILE', __FILE__ );
define( 'QAIYO_EASY_CHARTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'QAIYO_EASY_CHARTS_URL', plugin_dir_url( __FILE__ ) );

require_once QAIYO_EASY_CHARTS_PATH . 'includes/class-plugin.php';

\Qaiyo\EasyCharts\Plugin::instance();
