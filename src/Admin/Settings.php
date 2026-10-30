<?php
/**
 * Global settings: one option, one read/write contract.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Admin;

use Qaiyo\EasyCharts\Schema;
use Qaiyo\EasyCharts\Support\Cast;
use Qaiyo\EasyCharts\Support\Css;

defined( 'ABSPATH' ) || exit;

final class Settings {

	/** Colour settings and the CSS variable each one sets site-wide. */
	const COLOR_VARS = array(
		'bar_color'   => '--qec-primary',
		'range_color' => '--qec-secondary',
		'track_color' => '--qec-track',
		'label_color' => '--qec-label-color',
		'value_color' => '--qec-value-color',
		'axis_color'  => '--qec-axis-color',
	);

	/** @var array<string,mixed>|null */
	private static $cache = null;

	/**
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			'bar_color'        => '#0f0c24',
			'range_color'      => '#95e6d8',
			'track_color'      => '#ecebf1',
			'label_color'      => '#0f0c24',
			'value_color'      => '#0f0c24',
			'axis_color'       => '#6b6880',
			'font_family'      => '',
			'orientation'      => 'horizontal',
			'animate'          => true,
			'hover_focus'      => true,
			'data_table'       => true,
			'delete_on_uninstall' => false,
		);
	}

	/**
	 * Current settings merged over defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function get(): array {
		if ( null === self::$cache ) {
			self::$cache = self::sanitize( Cast::arr( get_option( Schema::OPTION_SETTINGS, array() ) ) );
		}
		return self::$cache;
	}

	public static function flush_cache(): void {
		self::$cache = null;
	}

	/**
	 * Sanitize callback for register_setting(). Starts from the defaults, so a
	 * partial submission never leaves a key undefined.
	 *
	 * @param mixed $input Submitted value.
	 * @return array<string,mixed>
	 */
	public static function sanitize( $input ): array {
		$input    = Cast::arr( $input );
		$defaults = self::defaults();
		$clean    = array();

		foreach ( array_keys( self::COLOR_VARS ) as $key ) {
			$color         = Css::color( $input[ $key ] ?? '' );
			$clean[ $key ] = '' !== $color ? $color : $defaults[ $key ];
		}

		$clean['font_family'] = Css::font_family( $input['font_family'] ?? '' );
		$clean['orientation'] = Cast::choice( $input['orientation'] ?? '', array( 'horizontal', 'vertical', 'auto' ), $defaults['orientation'] );

		foreach ( array( 'animate', 'hover_focus', 'data_table', 'delete_on_uninstall' ) as $flag ) {
			$clean[ $flag ] = array_key_exists( $flag, $input ) ? Cast::bool( $input[ $flag ] ) : ( empty( $input ) ? $defaults[ $flag ] : false );
		}

		return $clean;
	}

	/**
	 * Site-wide CSS defaults from the settings, at zero specificity so any
	 * per-chart or builder value wins.
	 */
	public static function inline_css(): string {
		$settings = self::get();
		$vars     = array();
		foreach ( self::COLOR_VARS as $key => $var ) {
			$vars[ $var ] = Css::color( $settings[ $key ] );
		}
		$vars['--qec-font-family'] = Css::font_family( $settings['font_family'] );

		$declarations = Css::inline( $vars );
		return '' === $declarations ? '' : ':where(.qec-chart){' . $declarations . '}';
	}
}
