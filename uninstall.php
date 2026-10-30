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
function qaiyo_ec_uninstall_site() {
	delete_transient( 'qaiyo_ec_more_plugins_wporg' );

	$settings = get_option( 'qaiyo_ec_settings', array() );
	if ( ! is_array( $settings ) || empty( $settings['delete_on_uninstall'] ) ) {
		return;
	}

	do {
		$chart_ids = get_posts(
			array(
				'post_type'      => 'qaiyo_ec_chart',
				'post_status'    => 'any',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $chart_ids as $chart_id ) {
			wp_delete_post( (int) $chart_id, true );
		}
	} while ( ! empty( $chart_ids ) );

	delete_option( 'qaiyo_ec_settings' );
}

if ( is_multisite() ) {
	$qaiyo_ec_sites = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $qaiyo_ec_sites as $qaiyo_ec_site_id ) {
		switch_to_blog( (int) $qaiyo_ec_site_id );
		qaiyo_ec_uninstall_site();
		restore_current_blog();
	}
	unset( $qaiyo_ec_sites, $qaiyo_ec_site_id );
} else {
	qaiyo_ec_uninstall_site();
}
