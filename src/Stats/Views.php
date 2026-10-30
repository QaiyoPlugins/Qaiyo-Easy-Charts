<?php
/**
 * Chart views: how many visitors actually saw a saved chart.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Stats;

use Qaiyo\EasyCharts\Admin\Settings;
use Qaiyo\EasyCharts\Schema;
use Qaiyo\EasyCharts\Support\Cast;

defined( 'ABSPATH' ) || exit;

/**
 * Counts a view when a saved chart scrolls into the visitor's viewport (not
 * merely when the page loads). chart.js reports it once per browser session to
 * a small public REST endpoint.
 *
 * Privacy by design: no cookies, no stored IP address or user data. To stop
 * repeated counting, a salted hash of IP + browser + chart is kept as a
 * transient for 30 minutes and then forgotten. Known bots are ignored, and
 * people who can edit charts are not counted unless the settings say so.
 *
 * Storage: a total and a per-day series of the last 30 days in post meta.
 *
 * @phpstan-type QecViewStats array{total: int, week: int, month: int, days: array<string, int>}
 */
final class Views {

	const META_TOTAL = '_qaiyo_ec_views';
	const META_DAILY = '_qaiyo_ec_views_daily';

	/** Days kept in the per-day series. */
	const DAYS = 30;

	/** The same visitor is counted again for the same chart after this many seconds. */
	const THROTTLE = 1800;

	const REST_NAMESPACE = 'qaiyo-easy-charts/v1';
	const REST_ROUTE     = '/views';

	const ACTION_RESET = 'qaiyo_ec_reset_views';

	/** User agents that are never counted. */
	const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|embedly|preview|headless|lighthouse|pingdom|uptime|monitor|curl|wget|python|java\//i';

	/**
	 * Hook this component into WordPress.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'route' ) );
		add_action( 'admin_post_' . self::ACTION_RESET, array( $this, 'reset_request' ) );
		add_action( 'admin_init', array( $this, 'privacy_text' ) );
	}

	/**
	 * Whether views are counted at all.
	 */
	public static function enabled(): bool {
		return (bool) Settings::get()['count_views'];
	}

	/**
	 * Whether the current page should report views of this chart. Charts are
	 * not tracked for people who can edit them, unless the settings say so.
	 *
	 * @param int $chart_id Saved chart ID (0 for inline charts).
	 */
	public static function should_track( int $chart_id ): bool {
		if ( $chart_id <= 0 || ! self::enabled() ) {
			return false;
		}
		return Settings::get()['count_editors'] || ! current_user_can( 'edit_post', $chart_id );
	}

	/**
	 * Endpoint URL for chart.js.
	 */
	public static function endpoint(): string {
		return rest_url( self::REST_NAMESPACE . self::REST_ROUTE );
	}

	/**
	 * Register the public REST route.
	 */
	public function route(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_count' ),
				// Public on purpose: anonymous visitors report what they saw. Nothing is
				// read or changed except one counter, and requests are throttled.
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'required'          => true,
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * REST callback: count one view.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public function rest_count( \WP_REST_Request $request ): \WP_REST_Response {
		$chart_id = Cast::int( $request->get_param( 'id' ) );
		$counted  = self::enabled() && self::countable( $chart_id ) && ! self::is_bot() && self::claim( $chart_id );
		if ( $counted ) {
			self::record( $chart_id );
		}
		return new \WP_REST_Response( array( 'counted' => $counted ), 200 );
	}

	/**
	 * Only published saved charts are counted.
	 *
	 * @param int $chart_id Chart ID.
	 */
	private static function countable( int $chart_id ): bool {
		$post = $chart_id > 0 ? get_post( $chart_id ) : null;
		return $post instanceof \WP_Post && Schema::POST_TYPE === $post->post_type && 'publish' === $post->post_status;
	}

	/**
	 * Whether the request comes from a crawler or a monitoring tool.
	 */
	private static function is_bot(): bool {
		$agent = self::server( 'HTTP_USER_AGENT' );
		return '' === $agent || 1 === preg_match( self::BOT_PATTERN, $agent );
	}

	/**
	 * A request header / server value as plain text ('' when missing).
	 *
	 * @param string $key $_SERVER key.
	 */
	private static function server( string $key ): string {
		return isset( $_SERVER[ $key ] ) && is_string( $_SERVER[ $key ] ) ? sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) : '';
	}

	/**
	 * Throttle: false when this visitor was already counted for this chart in
	 * the last 30 minutes. Only a salted hash is kept, and only for that long.
	 *
	 * @param int $chart_id Chart ID.
	 */
	private static function claim( int $chart_id ): bool {
		$fingerprint = self::server( 'REMOTE_ADDR' ) . '|' . self::server( 'HTTP_USER_AGENT' ) . '|' . $chart_id;
		$key         = 'qaiyo_ec_v_' . substr( hash_hmac( 'sha256', $fingerprint, wp_salt( 'nonce' ) ), 0, 32 );

		if ( false !== get_transient( $key ) ) {
			return false;
		}
		set_transient( $key, 1, self::THROTTLE );
		return true;
	}

	/**
	 * Add one view to the total and to today's count.
	 *
	 * @param int $chart_id Chart ID.
	 */
	public static function record( int $chart_id ): void {
		update_post_meta( $chart_id, self::META_TOTAL, self::total( $chart_id ) + 1 );

		$days  = self::days( $chart_id );
		$today = gmdate( 'Y-m-d' );

		$days[ $today ] = ( $days[ $today ] ?? 0 ) + 1;
		update_post_meta( $chart_id, self::META_DAILY, self::prune( $days, $today ) );
	}

	/**
	 * Total views of a chart.
	 *
	 * @param int $chart_id Chart ID.
	 */
	public static function total( int $chart_id ): int {
		return max( 0, Cast::int( get_post_meta( $chart_id, self::META_TOTAL, true ) ) );
	}

	/**
	 * Views per day (Y-m-d => count), as stored.
	 *
	 * @param int $chart_id Chart ID.
	 * @return array<string, int>
	 */
	private static function days( int $chart_id ): array {
		$days = array();
		foreach ( Cast::arr( get_post_meta( $chart_id, self::META_DAILY, true ) ) as $day => $count ) {
			if ( is_string( $day ) && 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $day ) ) {
				$days[ $day ] = max( 0, Cast::int( $count ) );
			}
		}
		return $days;
	}

	/**
	 * Keep only the last DAYS days.
	 *
	 * @param array<string, int> $days  Views per day.
	 * @param string             $today Today, Y-m-d (UTC).
	 * @return array<string, int>
	 */
	public static function prune( array $days, string $today ): array {
		$first = gmdate( 'Y-m-d', (int) strtotime( $today . ' -' . ( self::DAYS - 1 ) . ' days' ) );
		$days  = array_filter(
			$days,
			static fn( string $day ): bool => $day >= $first && $day <= $today,
			ARRAY_FILTER_USE_KEY
		);
		ksort( $days );
		return $days;
	}

	/**
	 * Total, last 7 and 30 days, and the full 30-day series (oldest first, zeros included).
	 *
	 * @param int $chart_id Chart ID.
	 * @return QecViewStats
	 */
	public static function stats( int $chart_id ): array {
		$stored = self::days( $chart_id );
		$today  = (int) strtotime( gmdate( 'Y-m-d' ) );

		$days = array();
		for ( $i = self::DAYS - 1; $i >= 0; $i-- ) {
			$day          = gmdate( 'Y-m-d', $today - $i * DAY_IN_SECONDS );
			$days[ $day ] = $stored[ $day ] ?? 0;
		}

		return array(
			'total' => self::total( $chart_id ),
			'week'  => array_sum( array_slice( $days, -7 ) ),
			'month' => array_sum( $days ),
			'days'  => $days,
		);
	}

	/**
	 * Admin action: reset a chart's counter.
	 */
	public function reset_request(): void {
		$chart_id = Cast::int( isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0 );
		check_admin_referer( self::ACTION_RESET . '_' . $chart_id );
		if ( ! current_user_can( 'edit_post', $chart_id ) ) {
			wp_die( esc_html__( 'You are not allowed to edit this chart.', 'qaiyo-easy-charts' ), '', array( 'response' => 403 ) );
		}

		delete_post_meta( $chart_id, self::META_TOTAL );
		delete_post_meta( $chart_id, self::META_DAILY );

		wp_safe_redirect( add_query_arg( 'qaiyo_ec_views_reset', '1', get_edit_post_link( $chart_id, 'url' ) ?? admin_url() ) );
		exit;
	}

	/**
	 * URL of the reset action.
	 *
	 * @param int $chart_id Chart ID.
	 */
	public static function reset_url( int $chart_id ): string {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action' => self::ACTION_RESET,
					'post'   => $chart_id,
				),
				admin_url( 'admin-post.php' )
			),
			self::ACTION_RESET . '_' . $chart_id
		);
	}

	/**
	 * Suggested text for the site's privacy policy.
	 */
	public function privacy_text(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		wp_add_privacy_policy_content(
			'Qaiyo Easy Charts',
			wp_kses_post(
				wpautop(
					__( 'When a chart scrolls into view, the site counts one anonymous view of that chart. No cookies are set and no personal data is stored: to avoid counting the same visitor twice, a one-way hash of the IP address and browser is kept for 30 minutes and then deleted automatically. Only the total number of views per chart and per day is stored.', 'qaiyo-easy-charts' )
				)
			)
		);
	}
}
