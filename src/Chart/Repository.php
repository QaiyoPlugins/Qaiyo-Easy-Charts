<?php
/**
 * Saved charts: the qec_chart post type plus its configuration meta.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Chart;

use Qaiyo\EasyCharts\Schema;
use Qaiyo\EasyCharts\Support\Cast;

defined( 'ABSPATH' ) || exit;

final class Repository {

	/**
	 * A saved chart's configuration, or null when it does not exist or may not
	 * be shown to the current visitor (unpublished charts render only for users
	 * who can edit them).
	 *
	 * @return array<string,mixed>|null
	 */
	public static function find( int $chart_id, bool $check_visibility = true ): ?array {
		$post = $chart_id > 0 ? get_post( $chart_id ) : null;
		if ( ! $post instanceof \WP_Post || Schema::POST_TYPE !== $post->post_type ) {
			return null;
		}
		if ( $check_visibility && 'publish' !== $post->post_status && ! current_user_can( 'edit_post', $post->ID ) ) {
			return null;
		}

		$config = ChartConfig::sanitize( Cast::arr( get_post_meta( $post->ID, Schema::META_CONFIG, true ) ) );
		if ( '' === $config['title'] ) {
			$config['title'] = get_the_title( $post );
		}
		return $config;
	}

	/**
	 * @param array<mixed> $config Raw configuration; sanitized here.
	 */
	public static function save( int $chart_id, array $config ): void {
		update_post_meta( $chart_id, Schema::META_CONFIG, ChartConfig::sanitize( $config ) );
	}

	/**
	 * Create a chart. Returns the new ID or a WP_Error.
	 *
	 * @param array<mixed> $config Raw configuration.
	 * @return int|\WP_Error
	 */
	public static function create( string $title, array $config, string $status ) {
		$chart_id = wp_insert_post(
			array(
				'post_type'   => Schema::POST_TYPE,
				'post_title'  => sanitize_text_field( $title ),
				'post_status' => in_array( $status, array( 'publish', 'draft' ), true ) ? $status : 'draft',
			),
			true
		);
		if ( is_wp_error( $chart_id ) ) {
			return $chart_id;
		}
		self::save( (int) $chart_id, $config );
		return (int) $chart_id;
	}

	/**
	 * Published and draft charts for pickers (bounded).
	 *
	 * @return list<array{id: int, title: string, status: string}>
	 */
	public static function list( int $limit = 200 ): array {
		$posts = get_posts(
			array(
				'post_type'        => Schema::POST_TYPE,
				'post_status'      => array( 'publish', 'draft', 'private' ),
				'posts_per_page'   => max( 1, min( 500, $limit ) ),
				'orderby'          => 'title',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		);

		$list = array();
		foreach ( $posts as $post ) {
			$list[] = array(
				'id'     => (int) $post->ID,
				'title'  => '' !== $post->post_title ? $post->post_title : sprintf( '#%d', $post->ID ),
				'status' => $post->post_status,
			);
		}
		return $list;
	}

	public static function shortcode( int $chart_id ): string {
		return sprintf( '[%s id="%d"]', Schema::SHORTCODE, $chart_id );
	}
}
