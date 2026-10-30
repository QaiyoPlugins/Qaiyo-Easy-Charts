<?php
/**
 * Saved charts post type and its admin list.
 *
 * Charts are not public pages: they are reusable data, embedded with the
 * shortcode, the block or a builder element. The post type is exposed to the
 * REST API (without its own front-end URL) so the block editor and agents can
 * discover charts.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Admin;

use Qaiyo\EasyCharts\Chart\Repository;
use Qaiyo\EasyCharts\Schema;
use Qaiyo\EasyCharts\Support\Cast;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the chart post type and its admin list columns.
 */
final class PostType {

	/** Top-level admin menu slug (the post type list). */
	const MENU_SLUG = 'edit.php?post_type=' . Schema::POST_TYPE;

	/**
	 * Hook this component into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_filter( 'manage_' . Schema::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . Schema::POST_TYPE . '_posts_custom_column', array( $this, 'cell' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'brand_menu' ), 5 );
		add_filter( 'post_updated_messages', array( $this, 'messages' ) );
	}

	/**
	 * Register the chart post type.
	 */
	public function register_post_type(): void {
		register_post_type(
			Schema::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Charts', 'qaiyo-easy-charts' ),
					'singular_name'      => __( 'Chart', 'qaiyo-easy-charts' ),
					'menu_name'          => __( 'Easy Charts', 'qaiyo-easy-charts' ),
					'all_items'          => __( 'All charts', 'qaiyo-easy-charts' ),
					'add_new'            => __( 'Add chart', 'qaiyo-easy-charts' ),
					'add_new_item'       => __( 'Add new chart', 'qaiyo-easy-charts' ),
					'edit_item'          => __( 'Edit chart', 'qaiyo-easy-charts' ),
					'new_item'           => __( 'New chart', 'qaiyo-easy-charts' ),
					'search_items'       => __( 'Search charts', 'qaiyo-easy-charts' ),
					'not_found'          => __( 'No charts yet.', 'qaiyo-easy-charts' ),
					'not_found_in_trash' => __( 'No charts in the trash.', 'qaiyo-easy-charts' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => true,
				'rest_base'           => 'qaiyo-charts',
				'menu_icon'           => 'dashicons-chart-bar',
				'menu_position'       => (int) BrandMenu::plugin_position(),
				'supports'            => array( 'title', 'revisions' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
			)
		);
	}

	/**
	 * Add the chart menu to the Qaiyo brand group.
	 */
	public function brand_menu(): void {
		BrandMenu::register_plugin_slug( self::MENU_SLUG );
	}

	/**
	 * Add the list table columns.
	 *
	 * @param mixed $columns Columns.
	 * @return mixed
	 */
	public function columns( $columns ) {
		if ( ! is_array( $columns ) ) {
			return $columns;
		}
		$date = $columns['date'] ?? null;
		unset( $columns['date'] );
		$columns['qaiyo_ec_shortcode'] = __( 'Shortcode', 'qaiyo-easy-charts' );
		$columns['qaiyo_ec_rows']      = __( 'Bars', 'qaiyo-easy-charts' );
		if ( null !== $date ) {
			$columns['date'] = $date;
		}
		return $columns;
	}

	/**
	 * Print one list table cell.
	 *
	 * @param mixed $column  Column key.
	 * @param mixed $post_id Post ID.
	 */
	public function cell( $column, $post_id ): void {
		$post_id = Cast::int( $post_id );
		if ( 'qaiyo_ec_shortcode' === $column ) {
			printf(
				'<code class="qec-shortcode-cell">%s</code>',
				esc_html( Repository::shortcode( $post_id ) )
			);
		} elseif ( 'qaiyo_ec_rows' === $column ) {
			$config = Repository::find( $post_id, false );
			echo esc_html( number_format_i18n( null === $config ? 0 : count( $config['items'] ) ) );
		}
	}

	/**
	 * Post-type specific "updated" notices.
	 *
	 * @param mixed $messages Messages.
	 * @return mixed
	 */
	public function messages( $messages ) {
		if ( ! is_array( $messages ) ) {
			return $messages;
		}
		$saved = __( 'Chart saved.', 'qaiyo-easy-charts' );

		$messages[ Schema::POST_TYPE ] = array(
			0  => '',
			1  => __( 'Chart updated.', 'qaiyo-easy-charts' ),
			4  => __( 'Chart updated.', 'qaiyo-easy-charts' ),
			6  => __( 'Chart published.', 'qaiyo-easy-charts' ),
			7  => $saved,
			8  => $saved,
			10 => __( 'Chart draft updated.', 'qaiyo-easy-charts' ),
		);
		return $messages;
	}
}
