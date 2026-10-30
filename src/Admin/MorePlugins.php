<?php
/**
 * Qaiyo "Discover Qaiyo" — WordPress.org-compliant discovery panel.
 *
 * Makes NO calls to qaiyo-plugins.com:
 *
 *   - Live section: the official WordPress.org plugin API (plugins_api,
 *   author=qaiyo) — WordPress's own infrastructure, cached in a transient,
 *   queried only when an admin opens this page.
 *   - Pro section: a small static, bundled list of paid extensions (plain
 *   links to qaiyo-plugins.com — no remote request is made by the plugin).
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Admin;

use Qaiyo\EasyCharts\Frontend\Assets;
use Qaiyo\EasyCharts\Schema;
use Qaiyo\EasyCharts\Support\Cast;

defined( 'ABSPATH' ) || exit;

/**
 * The "Discover Qaiyo" admin page.
 *
 * @phpstan-type QecWporgPlugin array{slug: string, name: string, description: string, icon: string, version: string}
 * @phpstan-type QecProPlugin array{name: string, desc: string, url: string}
 */
final class MorePlugins {

	const PAGE_SLUG     = 'qaiyo-ec-more-plugins';
	const TRANSIENT_KEY = Schema::TRANSIENT_MORE_PLUGINS;
	const WPORG_AUTHOR  = 'qaiyo';
	const CACHE_TTL     = 12 * HOUR_IN_SECONDS;

	/**
	 * Booted.
	 *
	 * @var bool
	 */
	private static $booted = false;

	/**
	 * Parents.
	 *
	 * @var list<string> Parent menu slugs the submenu is attached to.
	 */
	private static $parents = array();

	/**
	 * Page hooks.
	 *
	 * @var list<string>
	 */
	private static $page_hooks = array();

	/**
	 * Hook this component into WordPress.
	 *
	 * @param string $parent_slug Top-level menu slug to attach the submenu to.
	 */
	public static function register( string $parent_slug ): void {
		if ( '' !== $parent_slug && ! in_array( $parent_slug, self::$parents, true ) ) {
			self::$parents[] = $parent_slug;
		}

		if ( self::$booted ) {
			return;
		}
		self::$booted = true;

		add_action( 'admin_menu', array( __CLASS__, 'register_submenus' ), 80 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Add the "Discover Qaiyo" submenu.
	 */
	public static function register_submenus(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		foreach ( self::$parents as $parent ) {
			$hook = add_submenu_page(
				$parent,
				__( 'More plugins from Qaiyo', 'qaiyo-easy-charts' ),
				__( 'Discover Qaiyo', 'qaiyo-easy-charts' ),
				'manage_options',
				self::PAGE_SLUG . '-' . $parent,
				array( __CLASS__, 'render_page' )
			);
			if ( is_string( $hook ) && '' !== $hook ) {
				self::$page_hooks[] = $hook;
			}
		}
	}

	// Data — WordPress.org plugin directory (no call to our own server).

	/**
	 * This author's plugins from the official WordPress.org API. Cached in a
	 * transient; queried only on this admin page.
	 *
	 * @return list<QecWporgPlugin>
	 */
	public static function wporg_plugins(): array {
		$cached = get_transient( self::TRANSIENT_KEY );
		if ( is_array( $cached ) ) {
			return self::normalize_list( $cached );
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		$response = plugins_api(
			'query_plugins',
			array(
				'author'   => self::WPORG_AUTHOR,
				'per_page' => 24,
				'fields'   => array(
					'short_description' => true,
					'icons'             => true,
					'version'           => true,
				),
			)
		);

		$raw = array();
		if ( ! is_wp_error( $response ) && is_object( $response ) && isset( $response->plugins ) ) {
			$raw = Cast::arr( $response->plugins );
		}
		$list = self::normalize_list( $raw );

		// Cache even an empty result briefly so a failed call doesn't retry on every load.
		set_transient( self::TRANSIENT_KEY, $list, array() !== $list ? self::CACHE_TTL : HOUR_IN_SECONDS );
		return $list;
	}

	/**
	 * Shape the API's plugin objects (or a cached list) into flat records.
	 *
	 * @param array<mixed> $raw Plugin objects or arrays.
	 * @return list<QecWporgPlugin>
	 */
	public static function normalize_list( array $raw ): array {
		$list = array();
		foreach ( $raw as $plugin ) {
			$plugin = is_object( $plugin ) ? get_object_vars( $plugin ) : Cast::arr( $plugin );
			$slug   = sanitize_key( Cast::string( $plugin['slug'] ?? '' ) );
			if ( '' === $slug ) {
				continue;
			}

			$icons = $plugin['icons'] ?? array();
			$icons = is_object( $icons ) ? get_object_vars( $icons ) : Cast::arr( $icons );
			// A cached record already carries the flat 'icon'.
			$icon = Cast::string( $plugin['icon'] ?? '' );
			foreach ( array( 'svg', '2x', '1x', 'default' ) as $size ) {
				$candidate = Cast::string( $icons[ $size ] ?? '' );
				if ( '' === $icon && '' !== $candidate ) {
					$icon = $candidate;
					break;
				}
			}

			$list[] = array(
				'slug'        => $slug,
				'name'        => wp_strip_all_tags( Cast::string( $plugin['name'] ?? '' ) ),
				'description' => wp_strip_all_tags( Cast::string( $plugin['short_description'] ?? ( $plugin['description'] ?? '' ) ) ),
				'icon'        => $icon,
				'version'     => Cast::string( $plugin['version'] ?? '' ),
			);
		}
		return $list;
	}

	/**
	 * Static, bundled list of paid Qaiyo extensions (no remote call).
	 * Update this list when shipping a new Pro plugin.
	 *
	 * @return list<QecProPlugin>
	 */
	public static function pro_plugins(): array {
		$base = 'https://qaiyo-plugins.com/';
		return array(
			array(
				'name' => 'Qaiyo Social Video Embed Pro',
				'desc' => __( 'TikTok channel feeds and scheduled background refresh of your channel galleries.', 'qaiyo-easy-charts' ),
				'url'  => $base . 'qaiyo-social-video-embed/',
			),
			array(
				'name' => 'Qaiyo Testimonials Pro',
				'desc' => __( 'Extra layouts, star ratings, a front-end submission form and video testimonials.', 'qaiyo-easy-charts' ),
				'url'  => $base . 'qaiyo-testimonials/',
			),
			array(
				'name' => 'Qaiyo Access Manager Pro',
				'desc' => __( 'Per-term taxonomy access, advanced per-user rules and finer-grained access control.', 'qaiyo-easy-charts' ),
				'url'  => $base . 'qaiyo-access-manager/',
			),
			array(
				'name' => 'Qaiyo Featured Image Bulk Replacer Pro',
				'desc' => __( 'Background processing, scheduling, automatic image sourcing and full change history.', 'qaiyo-easy-charts' ),
				'url'  => $base . 'qaiyo-featured-image-bulk-replacer/',
			),
			array(
				'name' => 'Qaiyo Smart Appointment Pro',
				'desc' => __( 'Unlimited services and staff, extra booking layouts and advanced scheduling rules.', 'qaiyo-easy-charts' ),
				'url'  => $base . 'qaiyo-smart-appointment/',
			),
			array(
				'name' => 'Qaiyo Admin Booster Pro',
				'desc' => __( 'Advanced wp-admin tools and workflow enhancements that build on the free modules.', 'qaiyo-easy-charts' ),
				'url'  => $base . 'qaiyo-admin-booster/',
			),
			array(
				'name' => 'Qaiyo Web Performance Surgeon Pro',
				'desc' => __( 'Deeper performance diagnostics, more optimisation rules and one-click fixes.', 'qaiyo-easy-charts' ),
				'url'  => $base . 'qaiyo-web-performance-surgeon/',
			),
		);
	}

	// Local install/active detection (no remote call).

	/**
	 * 'active', 'installed' or 'none' for a WordPress.org slug.
	 *
	 * @param string            $slug   Plugin slug.
	 * @param array<mixed>|null $installed get_plugins() result, if already loaded.
	 */
	public static function status( string $slug, ?array $installed = null ): string {
		if ( null === $installed ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$installed = get_plugins();
		}
		foreach ( array_keys( $installed ) as $file ) {
			$file = (string) $file;
			if ( str_starts_with( $file, $slug . '/' ) || $slug . '.php' === $file ) {
				return is_plugin_active( $file ) ? 'active' : 'installed';
			}
		}
		return 'none';
	}

	// Render.

	/**
	 * Render the "Discover Qaiyo" page.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$installed   = get_plugins();
		$can_install = current_user_can( 'install_plugins' );

		$cards = array();
		foreach ( self::wporg_plugins() as $plugin ) {
			$status  = self::status( $plugin['slug'], $installed );
			$cards[] = array(
				'name'        => $plugin['name'],
				'description' => $plugin['description'],
				'icon'        => $plugin['icon'],
				'monogram'    => self::monogram( $plugin['name'] ),
				'status'      => $status,
				'install_url' => ( 'none' === $status && $can_install )
					? wp_nonce_url(
						self_admin_url( 'update.php?action=install-plugin&plugin=' . rawurlencode( $plugin['slug'] ) ),
						'install-plugin_' . $plugin['slug']
					)
					: '',
				'details_url' => 'https://wordpress.org/plugins/' . $plugin['slug'] . '/',
			);
		}

		$pro = array();
		foreach ( self::pro_plugins() as $plugin ) {
			$plugin['monogram'] = self::monogram( $plugin['name'] );
			$pro[]              = $plugin;
		}

		View::output(
			'more-plugins',
			array(
				'qaiyo_ec_cards' => $cards,
				'qaiyo_ec_pro'   => $pro,
			)
		);
	}

	// Assets.

	/**
	 * Action callback: WordPress passes the hook suffix, untyped on purpose.
	 *
	 * @param mixed $hook_suffix Current admin page.
	 */
	public static function enqueue_assets( mixed $hook_suffix = '' ): void {
		if ( ! in_array( Cast::string( $hook_suffix ), self::$page_hooks, true ) ) {
			return;
		}
		wp_enqueue_style(
			Schema::HANDLE_MORE_PLUGINS,
			QAIYO_EC_PLUGIN_URL . 'assets/css/more-plugins.css',
			array( 'dashicons' ),
			Assets::version( 'assets/css/more-plugins.css' )
		);
	}

	// Helpers.

	/**
	 * Monogram.
	 *
	 * @param string $name Name.
	 */
	public static function monogram( string $name ): string {
		$name = trim( wp_strip_all_tags( $name ) );
		if ( '' === $name ) {
			return 'Q';
		}
		// WordPress polyfills mb_substr() but not mb_strtoupper().
		$first = mb_substr( $name, 0, 1 );
		return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $first, 'UTF-8' ) : strtoupper( $first );
	}
}
