<?php
/**
 * Chart edit screen: data rows, settings, live preview and embed codes.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Admin;

use Qaiyo\EasyCharts\Chart\ChartConfig;
use Qaiyo\EasyCharts\Chart\Renderer;
use Qaiyo\EasyCharts\Chart\Repository;
use Qaiyo\EasyCharts\Frontend\Assets;
use Qaiyo\EasyCharts\Schema;
use Qaiyo\EasyCharts\Support\Cast;

defined( 'ABSPATH' ) || exit;

final class ChartEditor {

	/** Form field name holding the configuration array. */
	const FIELD = 'qec_config';

	public function register(): void {
		add_action( 'add_meta_boxes_' . Schema::POST_TYPE, array( $this, 'meta_boxes' ) );
		add_action( 'save_post_' . Schema::POST_TYPE, array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_ajax_' . Schema::AJAX_PREVIEW, array( $this, 'ajax_preview' ) );
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		add_action( 'edit_form_top', array( $this, 'intro' ) );
	}

	private static function is_chart_screen(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen instanceof \WP_Screen && Schema::POST_TYPE === $screen->post_type;
	}

	/**
	 * @param mixed $classes Body classes.
	 * @return mixed
	 */
	public function body_class( $classes ) {
		return self::is_chart_screen() && is_string( $classes ) ? $classes . ' qec-admin-screen' : $classes;
	}

	public function meta_boxes(): void {
		add_meta_box( 'qec-chart-builder', __( 'Chart builder', 'qaiyo-easy-charts' ), array( $this, 'render_builder' ), Schema::POST_TYPE, 'normal', 'high' );
		add_meta_box( 'qec-chart-embed', __( 'Embed', 'qaiyo-easy-charts' ), array( $this, 'render_embed' ), Schema::POST_TYPE, 'side', 'default' );
	}

	/**
	 * Branded heading above the title field.
	 *
	 * @param mixed $post Post being edited.
	 */
	public function intro( $post ): void {
		if ( ! $post instanceof \WP_Post || Schema::POST_TYPE !== $post->post_type ) {
			return;
		}
		View::output( 'editor-intro', array( 'qec_greeting_name' => View::greeting_name() ) );
	}

	/**
	 * @param mixed $post Post being edited.
	 */
	public function render_builder( $post ): void {
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$config = 'auto-draft' === $post->post_status
			? ChartConfig::demo()
			: ( Repository::find( $post->ID, false ) ?? ChartConfig::demo() );

		// The title comes from the post title field; keep the stored one only if it differs.
		$stored = Cast::arr( get_post_meta( $post->ID, Schema::META_CONFIG, true ) );
		$config['title'] = Cast::string( $stored['title'] ?? '' );

		View::output(
			'chart-editor',
			array(
				'qec_config'  => $config,
				'qec_field'   => self::FIELD,
				'qec_preview' => Renderer::render( self::preview_config( $config, $post ), array(), false ),
			)
		);
	}

	/**
	 * @param mixed $post Post being edited.
	 */
	public function render_embed( $post ): void {
		if ( ! $post instanceof \WP_Post ) {
			return;
		}
		View::output(
			'chart-embed',
			array(
				'qec_shortcode' => Repository::shortcode( $post->ID ),
				'qec_is_saved'  => 'auto-draft' !== $post->post_status,
			)
		);
	}

	/**
	 * @param mixed $post_id Post ID.
	 * @param mixed $post    Post.
	 */
	public function save( $post_id, $post ): void {
		$post_id = Cast::int( $post_id );

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['qec_editor_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qec_editor_nonce'] ) ), Schema::NONCE_EDITOR ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Sanitized field by field in ChartConfig::sanitize().
		$raw = isset( $_POST[ self::FIELD ] ) ? wp_unslash( $_POST[ self::FIELD ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		Repository::save( $post_id, self::from_form( Cast::arr( $raw ) ) );
	}

	/**
	 * The form posts checkboxes only when ticked and rows keyed by a counter.
	 *
	 * @param array<mixed> $raw Raw form data.
	 * @return array<string,mixed>
	 */
	public static function from_form( array $raw ): array {
		foreach ( array( 'show_caption', 'show_axis', 'show_values', 'animate', 'hover_focus', 'data_table' ) as $flag ) {
			$raw[ $flag ] = ! empty( $raw[ $flag ] );
		}
		$raw['items'] = array_values( Cast::arr( $raw['items'] ?? array() ) );
		return $raw;
	}

	/**
	 * In the admin the title shown in the preview falls back to the post title.
	 *
	 * @param array<string,mixed> $config Configuration.
	 * @return array<string,mixed>
	 */
	private static function preview_config( array $config, \WP_Post $post ): array {
		if ( '' === $config['title'] ) {
			$config['title'] = get_the_title( $post );
		}
		$config['animate'] = false;
		return $config;
	}

	public function ajax_preview(): void {
		check_ajax_referer( Schema::NONCE_PREVIEW, 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to edit this chart.', 'qaiyo-easy-charts' ) ), 403 );
		}

		// Sanitized field by field in ChartConfig::sanitize().
		$raw    = isset( $_POST[ self::FIELD ] ) ? wp_unslash( $_POST[ self::FIELD ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$config = ChartConfig::sanitize( self::from_form( Cast::arr( $raw ) ) );

		$post = get_post( $post_id );
		if ( '' === $config['title'] && $post instanceof \WP_Post ) {
			$config['title'] = isset( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : get_the_title( $post );
		}
		$config['animate'] = false;

		wp_send_json_success( array( 'html' => Renderer::render( $config, array(), false ) ) );
	}

	/**
	 * @param mixed $hook_suffix Current admin page.
	 */
	public function enqueue( $hook_suffix ): void {
		if ( ! self::is_chart_screen() ) {
			return;
		}

		wp_enqueue_style( Schema::HANDLE_ADMIN, QEC_PLUGIN_URL . 'assets/css/admin.css', array(), Assets::version( 'assets/css/admin.css' ) );

		if ( ! in_array( Cast::string( $hook_suffix ), array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		Assets::enqueue();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			Schema::HANDLE_EDITOR,
			QEC_PLUGIN_URL . 'assets/js/chart-editor.js',
			array( 'jquery', 'wp-color-picker', 'wp-a11y' ),
			Assets::version( 'assets/js/chart-editor.js' ),
			array( 'in_footer' => true )
		);

		$post = get_post();
		wp_add_inline_script(
			Schema::HANDLE_EDITOR,
			'window.qecEditor = ' . wp_json_encode(
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'action'  => Schema::AJAX_PREVIEW,
					'nonce'   => wp_create_nonce( Schema::NONCE_PREVIEW ),
					'postId'  => $post instanceof \WP_Post ? $post->ID : 0,
					'field'   => self::FIELD,
					'i18n'    => array(
						'rowAdded'    => __( 'Bar added.', 'qaiyo-easy-charts' ),
						'rowRemoved'  => __( 'Bar removed.', 'qaiyo-easy-charts' ),
						'rowMoved'    => __( 'Bar moved.', 'qaiyo-easy-charts' ),
						'copied'      => __( 'Copied to the clipboard.', 'qaiyo-easy-charts' ),
						'previewFail' => __( 'The preview could not be refreshed.', 'qaiyo-easy-charts' ),
					),
				)
			) . ';',
			'before'
		);
	}
}
