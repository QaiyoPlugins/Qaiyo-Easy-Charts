<?php
/**
 * Uninstall handler for Qaiyo Easy Charts.
 *
 * Charts are content, so they are kept unless "Delete all charts and settings
 * when the plugin is deleted" was switched on. Cached data is always removed.
 * Runs without the plugin's classes, so every identifier is inlined here.
 *
 * On multisite every site is cleaned, not just the current one.
 *
 * @package qaiyo-easy-charts
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove this plugin's data on the current site.
 *
 * @return void
 */
function qec_uninstall_site() {
	delete_transient( 'qec_more_plugins_wporg' );

	$settings = get_option( 'qec_settings', array() );
	if ( ! is_array( $settings ) || empty( $settings['delete_on_uninstall'] ) ) {
		return;
	}

	do {
		$chart_ids = get_posts(
			array(
				'post_type'        => 'qec_chart',
				'post_status'      => 'any',
				'posts_per_page'   => 200,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);
		foreach ( $chart_ids as $chart_id ) {
			wp_delete_post( (int) $chart_id, true );
		}
	} while ( ! empty( $chart_ids ) );

	delete_option( 'qec_settings' );
}

if ( is_multisite() ) {
	$qec_sites = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $qec_sites as $qec_site_id ) {
		switch_to_blog( (int) $qec_site_id );
		qec_uninstall_site();
		restore_current_blog();
	}
	unset( $qec_sites, $qec_site_id );
} else {
	qec_uninstall_site();
}
