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
use Qaiyo\EasyCharts\Stats\Views;
use Qaiyo\EasyCharts\Support\Cast;

defined( 'ABSPATH' ) || exit;

/**
 * The chart edit screen: meta boxes, saving and live preview.
 *
 * @phpstan-import-type QecConfig from \Qaiyo\EasyCharts\Chart\ChartConfig
 */
final class ChartEditor {

	/** Form field name holding the configuration array. */
	const FIELD = 'qaiyo_ec_config';

	/**
	 * Hook this component into WordPress.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes_' . Schema::POST_TYPE, array( $this, 'meta_boxes' ) );
		add_action( 'save_post_' . Schema::POST_TYPE, array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_ajax_' . Schema::AJAX_PREVIEW, array( $this, 'ajax_preview' ) );
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		add_action( 'edit_form_top', array( $this, 'intro' ) );
	}

	/**
	 * Whether the current admin screen belongs to the chart post type.
	 */
	private static function is_chart_screen(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen instanceof \WP_Screen && Schema::POST_TYPE === $screen->post_type;
	}

	/**
	 * Mark the chart screens with a body class.
	 *
	 * @param mixed $classes Body classes.
	 * @return mixed
	 */
	public function body_class( mixed $classes ): mixed {
		return self::is_chart_screen() && is_string( $classes ) ? $classes . ' qec-admin-screen' : $classes;
	}

	/**
	 * Add the chart builder and embed meta boxes.
	 */
	public function meta_boxes(): void {
		add_meta_box( 'qec-chart-builder', __( 'Chart builder', 'qaiyo-easy-charts' ), array( $this, 'render_builder' ), Schema::POST_TYPE, 'normal', 'high' );
		add_meta_box( 'qec-chart-embed', __( 'Embed', 'qaiyo-easy-charts' ), array( $this, 'render_embed' ), Schema::POST_TYPE, 'side', 'default' );
		add_meta_box( 'qec-chart-views', __( 'Views', 'qaiyo-easy-charts' ), array( $this, 'render_views' ), Schema::POST_TYPE, 'side', 'default' );
	}

	/**
	 * Branded heading above the title field.
	 *
	 * @param mixed $post Post being edited.
	 */
	public function intro( mixed $post ): void {
		if ( ! $post instanceof \WP_Post || Schema::POST_TYPE !== $post->post_type ) {
			return;
		}
		View::output( 'editor-intro', array( 'qaiyo_ec_greeting_name' => View::greeting_name() ) );
	}

	/**
	 * Render the chart builder meta box.
	 *
	 * @param mixed $post Post being edited.
	 */
	public function render_builder( mixed $post ): void {
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$config = 'auto-draft' === $post->post_status
			? ChartConfig::demo()
			: ( Repository::find( $post->ID, false ) ?? ChartConfig::demo() );

		// The title comes from the post title field; keep the stored one only if it differs.
		$stored          = Cast::arr( get_post_meta( $post->ID, Schema::META_CONFIG, true ) );
		$config['title'] = Cast::string( $stored['title'] ?? '' );

		View::output(
			'chart-editor',
			array(
				'qaiyo_ec_config'  => $config,
				'qaiyo_ec_field'   => self::FIELD,
				'qaiyo_ec_preview' => Renderer::render( self::preview_config( $config, $post ), array(), false ),
			)
		);
	}

	/**
	 * Render the embed meta box.
	 *
	 * @param mixed $post Post being edited.
	 */
	public function render_embed( mixed $post ): void {
		if ( ! $post instanceof \WP_Post ) {
			return;
		}
		View::output(
			'chart-embed',
			array(
				'qaiyo_ec_shortcode' => Repository::shortcode( $post->ID ),
				'qaiyo_ec_is_saved'  => 'auto-draft' !== $post->post_status,
			)
		);
	}

	/**
	 * Render the views meta box.
	 *
	 * @param mixed $post Post being edited.
	 */
	public function render_views( mixed $post ): void {
		if ( ! $post instanceof \WP_Post ) {
			return;
		}
		View::output(
			'chart-views',
			array(
				'qaiyo_ec_stats'     => Views::stats( $post->ID ),
				'qaiyo_ec_enabled'   => Views::enabled(),
				'qaiyo_ec_reset_url' => Views::reset_url( $post->ID ),
				'qaiyo_ec_was_reset' => isset( $_GET['qaiyo_ec_views_reset'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display flag only.
				'qaiyo_ec_settings'  => admin_url( 'admin.php?page=' . Schema::SETTINGS_PAGE ),
			)
		);
	}

	/**
	 * Save the chart configuration.
	 *
	 * @param mixed $post_id Post ID.
	 */
	public function save( mixed $post_id ): void {
		$post_id = Cast::int( $post_id );

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		$nonce = isset( $_POST['qaiyo_ec_editor_nonce'] ) && is_string( $_POST['qaiyo_ec_editor_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['qaiyo_ec_editor_nonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, Schema::NONCE_EDITOR ) ) {
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
	 * @return array<mixed>
	 */
	public static function from_form( array $raw ): array {
		foreach ( array( 'show_caption', 'show_axis', 'show_values', 'animate', 'replay', 'count_up', 'lazy', 'hover_focus', 'data_table', 'patterns', 'tooltips', 'export' ) as $flag ) {
			$raw[ $flag ] = ! empty( $raw[ $flag ] );
		}
		$raw['items'] = array_values( Cast::arr( $raw['items'] ?? array() ) );
		return $raw;
	}

	/**
	 * In the admin the title shown in the preview falls back to the post title.
	 *
	 * @param array    $config Configuration.
	 * @phpstan-param QecConfig $config
	 * @param \WP_Post $post   Chart post.
	 * @return QecConfig
	 */
	private static function preview_config( array $config, \WP_Post $post ): array {
		if ( '' === $config['title'] ) {
			$config['title'] = get_the_title( $post );
		}
		// The preview is drawn at once, plays its animation only on request and is never counted as a view.
		$config['animate']  = false;
		$config['lazy']     = false;
		$config['chart_id'] = 0;
		return $config;
	}

	/**
	 * Return the rendered preview for the chart editor.
	 */
	public function ajax_preview(): void {
		check_ajax_referer( Schema::NONCE_PREVIEW, 'nonce' );

		$post_id = Cast::int( isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0 );
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to edit this chart.', 'qaiyo-easy-charts' ) ), 403 );
		}

		// Sanitized field by field in ChartConfig::sanitize().
		$raw    = isset( $_POST[ self::FIELD ] ) ? wp_unslash( $_POST[ self::FIELD ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$config = ChartConfig::sanitize( self::from_form( Cast::arr( $raw ) ) );

		$post = get_post( $post_id );
		if ( '' === $config['title'] && $post instanceof \WP_Post ) {
			$config['title'] = isset( $_POST['post_title'] ) && is_string( $_POST['post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['post_title'] ) ) : get_the_title( $post );
		}
		// Animation plays only when the "Play animation" button asked for it; never lazy.
		$config['animate']  = $config['animate'] && ! empty( $_POST['play'] );
		$config['replay']   = false;
		$config['lazy']     = false;
		$config['chart_id'] = 0;

		wp_send_json_success( array( 'html' => Renderer::render( $config, array(), false ) ) );
	}

	/**
	 * Styles of the other saved charts, for "Copy the style of another chart".
	 *
	 * @param int $current_id The chart being edited.
	 * @return list<array{id: int, title: string, style: array<string, mixed>}>
	 */
	private static function other_styles( int $current_id ): array {
		$styles = array();
		foreach ( Repository::list( 100 ) as $chart ) {
			if ( $chart['id'] === $current_id ) {
				continue;
			}
			$config = Repository::find( $chart['id'], false );
			if ( null !== $config ) {
				$styles[] = array(
					'id'    => $chart['id'],
					'title' => $chart['title'],
					'style' => ChartConfig::style_of( $config ),
				);
			}
		}
		return $styles;
	}

	/**
	 * Enqueue the assets.
	 *
	 * @param mixed $hook_suffix Current admin page.
	 */
	public function enqueue( mixed $hook_suffix ): void {
		if ( ! self::is_chart_screen() ) {
			return;
		}

		wp_enqueue_style( Schema::HANDLE_ADMIN, QAIYO_EC_PLUGIN_URL . 'assets/css/admin.css', array(), Assets::version( 'assets/css/admin.css' ) );

		if ( ! in_array( Cast::string( $hook_suffix ), array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		Assets::enqueue();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( Schema::HANDLE_TYPE_PICKER, QAIYO_EC_PLUGIN_URL . 'assets/css/type-picker.css', array(), Assets::version( 'assets/css/type-picker.css' ) );
		wp_enqueue_script(
			Schema::HANDLE_EDITOR,
			QAIYO_EC_PLUGIN_URL . 'assets/js/chart-editor.js',
			array( 'jquery', 'wp-color-picker', 'wp-a11y' ),
			Assets::version( 'assets/js/chart-editor.js' ),
			array( 'in_footer' => true )
		);

		$post_id = get_post()->ID ?? 0;
		wp_add_inline_script(
			Schema::HANDLE_EDITOR,
			'window.qaiyoEcEditor = ' . wp_json_encode(
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'action'  => Schema::AJAX_PREVIEW,
					'nonce'   => wp_create_nonce( Schema::NONCE_PREVIEW ),
					'postId'  => $post_id,
					'field'   => self::FIELD,
					'types'   => ChartConfig::type_labels(),
					'styles'  => self::other_styles( $post_id ),
					'i18n'    => array(
						/* translators: %d: number of imported rows. */
						'imported'        => __( 'Rows imported: %d.', 'qaiyo-easy-charts' ),
						'importEmpty'     => __( 'No rows found. Paste one row per line: label, value, second value, value text.', 'qaiyo-easy-charts' ),
						'styleCopied'     => __( 'Style copied. Save the chart to keep it.', 'qaiyo-easy-charts' ),
						'rowAdded'        => __( 'Bar added.', 'qaiyo-easy-charts' ),
						'rowRemoved'      => __( 'Bar removed.', 'qaiyo-easy-charts' ),
						'rowMoved'        => __( 'Bar moved.', 'qaiyo-easy-charts' ),
						'copied'          => __( 'Copied to the clipboard.', 'qaiyo-easy-charts' ),
						'previewFail'     => __( 'The preview could not be refreshed.', 'qaiyo-easy-charts' ),
						'noAnimation'     => __( 'Animation is switched off for this chart.', 'qaiyo-easy-charts' ),
						'typeChanged'     => __( 'Chart type changed. The row fields follow the new type.', 'qaiyo-easy-charts' ),
						'resetViews'      => __( 'Reset the view counter of this chart? This cannot be undone.', 'qaiyo-easy-charts' ),
						/* translators: 1: colour setting name, 2: contrast ratio such as 3.2. */
						'lowContrast'     => __( '%1$s has a contrast of %2$s:1 on a white background. Text needs at least 4.5:1 to meet WCAG AA.', 'qaiyo-easy-charts' ),
						'contrastOk'      => __( 'All text colours meet the WCAG AA contrast of 4.5:1 on a white background.', 'qaiyo-easy-charts' ),
						/* translators: 1: colour setting name, 2: contrast ratio such as 3.2. */
						'lowContrastDark' => __( 'Dark mode: %1$s has a contrast of %2$s:1 on the dark background. Text needs at least 4.5:1 to meet WCAG AA.', 'qaiyo-easy-charts' ),
						'contrastDarkOk'  => __( 'Dark mode: all text colours meet the WCAG AA contrast of 4.5:1 on the dark background.', 'qaiyo-easy-charts' ),
					),
				)
			) . ';',
			'before'
		);
	}
}
