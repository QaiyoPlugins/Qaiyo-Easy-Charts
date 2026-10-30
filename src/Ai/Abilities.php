<?php
/**
 * WordPress Abilities API (WP 6.9+): lets AI agents and MCP clients discover,
 * read and create charts through a typed, permission-checked contract.
 *
 * Feature-detected: on older WordPress versions nothing is registered.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Ai;

use Qaiyo\EasyCharts\Chart\ChartConfig;
use Qaiyo\EasyCharts\Chart\Repository;
use Qaiyo\EasyCharts\Schema;
use Qaiyo\EasyCharts\Support\Cast;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the chart abilities for AI agents.
 */
final class Abilities {

	/**
	 * Hook this component into WordPress.
	 */
	public function register(): void {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'abilities' ) );
	}

	/**
	 * Register the ability category.
	 */
	public function category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}
		wp_register_ability_category(
			Schema::ABILITY_CATEGORY,
			array(
				'label'       => __( 'Qaiyo Easy Charts', 'qaiyo-easy-charts' ),
				'description' => __( 'Create and read bar and column charts that can be embedded with a shortcode.', 'qaiyo-easy-charts' ),
			)
		);
	}

	/**
	 * Register the abilities.
	 */
	public function abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		$item_schema = array(
			'type'       => 'object',
			'required'   => array( 'label', 'value' ),
			'properties' => array(
				'label' => array(
					'type'        => 'string',
					'description' => __( 'Label of the bar.', 'qaiyo-easy-charts' ),
				),
				'value' => array(
					'type'        => 'number',
					'description' => __( 'Where the solid bar ends.', 'qaiyo-easy-charts' ),
				),
				'max'   => array(
					'type'        => 'number',
					'description' => __( 'Optional end of a highlighted range after the value, e.g. 15 for "10–15".', 'qaiyo-easy-charts' ),
				),
				'text'  => array(
					'type'        => 'string',
					'description' => __( 'Optional text shown instead of the number, e.g. "up to 5".', 'qaiyo-easy-charts' ),
				),
			),
		);

		$chart_summary = array(
			'type'       => 'object',
			'properties' => array(
				'id'        => array( 'type' => 'integer' ),
				'title'     => array( 'type' => 'string' ),
				'status'    => array( 'type' => 'string' ),
				'shortcode' => array( 'type' => 'string' ),
			),
		);

		wp_register_ability(
			'qaiyo-easy-charts/list-charts',
			array(
				'label'               => __( 'List charts', 'qaiyo-easy-charts' ),
				'description'         => __( 'Lists the saved charts with their ID, title, status and the shortcode that embeds each one.', 'qaiyo-easy-charts' ),
				'category'            => Schema::ABILITY_CATEGORY,
				'output_schema'       => array(
					'type'  => 'array',
					'items' => $chart_summary,
				),
				'execute_callback'    => array( self::class, 'list_charts' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);

		wp_register_ability(
			'qaiyo-easy-charts/get-chart',
			array(
				'label'               => __( 'Get chart', 'qaiyo-easy-charts' ),
				'description'         => __( 'Returns one saved chart: its bars (label, value, optional range end and text), unit, orientation and shortcode.', 'qaiyo-easy-charts' ),
				'category'            => Schema::ABILITY_CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'id' ),
					'properties' => array(
						'id' => array(
							'type'        => 'integer',
							'description' => __( 'Chart ID.', 'qaiyo-easy-charts' ),
							'minimum'     => 1,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'          => array( 'type' => 'integer' ),
						'title'       => array( 'type' => 'string' ),
						'shortcode'   => array( 'type' => 'string' ),
						'orientation' => array( 'type' => 'string' ),
						'unit'        => array( 'type' => 'string' ),
						'items'       => array(
							'type'  => 'array',
							'items' => $item_schema,
						),
					),
				),
				'execute_callback'    => array( self::class, 'get_chart' ),
				'permission_callback' => static function ( $input ): bool {
					return current_user_can( 'edit_post', Cast::int( Cast::arr( $input )['id'] ?? 0 ) );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);

		wp_register_ability(
			'qaiyo-easy-charts/create-chart',
			array(
				'label'               => __( 'Create chart', 'qaiyo-easy-charts' ),
				'description'         => __( 'Creates a saved bar or column chart and returns its ID and the shortcode to place it in content. Each bar has a label and a value; an optional range end draws a highlighted range (e.g. 10–15 days).', 'qaiyo-easy-charts' ),
				'category'            => Schema::ABILITY_CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'required'   => array( 'title', 'items' ),
					'properties' => array(
						'title'       => array(
							'type'        => 'string',
							'description' => __( 'Chart title; also its accessible name.', 'qaiyo-easy-charts' ),
							'minLength'   => 1,
							'maxLength'   => 200,
						),
						'items'       => array(
							'type'     => 'array',
							'minItems' => 1,
							'maxItems' => ChartConfig::MAX_ITEMS,
							'items'    => $item_schema,
						),
						'orientation' => array(
							'type'    => 'string',
							'enum'    => ChartConfig::ORIENTATIONS,
							'default' => 'horizontal',
						),
						'unit'        => array(
							'type'        => 'string',
							'description' => __( 'Unit shown on the axis, e.g. "days".', 'qaiyo-easy-charts' ),
						),
						'publish'     => array(
							'type'        => 'boolean',
							'description' => __( 'Publish immediately (otherwise saved as a draft).', 'qaiyo-easy-charts' ),
							'default'     => false,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'        => array( 'type' => 'integer' ),
						'shortcode' => array( 'type' => 'string' ),
						'edit_url'  => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => array( self::class, 'create_chart' ),
				'permission_callback' => static function ( $input ): bool {
					$publish = Cast::bool( Cast::arr( $input )['publish'] ?? false );
					return current_user_can( 'edit_posts' ) && ( ! $publish || current_user_can( 'publish_posts' ) );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => false,
					),
				),
			)
		);
	}

	/**
	 * Execute: list charts.
	 *
	 * @return list<array<string,mixed>>
	 */
	public static function list_charts(): array {
		return array_map(
			static function ( array $chart ): array {
				return $chart + array( 'shortcode' => Repository::shortcode( $chart['id'] ) );
			},
			Repository::list()
		);
	}

	/**
	 * Execute: get one chart.
	 *
	 * @param mixed $input Validated input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function get_chart( $input ) {
		$id     = Cast::int( Cast::arr( $input )['id'] ?? 0 );
		$config = Repository::find( $id, false );
		if ( null === $config ) {
			return new \WP_Error( 'qaiyo_ec_not_found', __( 'Chart not found.', 'qaiyo-easy-charts' ) );
		}

		$items = array();
		foreach ( $config['items'] as $item ) {
			$out = array(
				'label' => $item['label'],
				'value' => $item['value'],
			);
			if ( '' !== $item['max'] ) {
				$out['max'] = (float) $item['max'];
			}
			if ( '' !== $item['text'] ) {
				$out['text'] = $item['text'];
			}
			$items[] = $out;
		}

		return array(
			'id'          => $id,
			'title'       => $config['title'],
			'shortcode'   => Repository::shortcode( $id ),
			'orientation' => $config['orientation'],
			'unit'        => $config['unit'],
			'items'       => $items,
		);
	}

	/**
	 * Execute: create a chart.
	 *
	 * @param mixed $input Validated input.
	 * @return array<string,mixed>|\WP_Error
	 */
	public static function create_chart( $input ) {
		$input = Cast::arr( $input );
		$title = Cast::string( $input['title'] ?? '' );

		$config = array(
			'items'       => Cast::arr( $input['items'] ?? array() ),
			'orientation' => $input['orientation'] ?? 'horizontal',
			'unit'        => $input['unit'] ?? '',
			'title'       => $title,
		);
		if ( array() === ChartConfig::sanitize_items( $config['items'] ) ) {
			return new \WP_Error( 'qaiyo_ec_no_items', __( 'At least one bar with a label and a value is required.', 'qaiyo-easy-charts' ) );
		}

		$status = Cast::bool( $input['publish'] ?? false ) ? 'publish' : 'draft';
		$id     = Repository::create( $title, $config, $status );
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		return array(
			'id'        => $id,
			'shortcode' => Repository::shortcode( $id ),
			'edit_url'  => (string) get_edit_post_link( $id, 'raw' ),
		);
	}
}
