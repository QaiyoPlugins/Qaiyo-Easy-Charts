<?php
/**
 * Global behaviour settings: one option, one read/write contract.
 *
 * Deliberately no colours or fonts here: every chart is styled on its own.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Admin;

use Qaiyo\EasyCharts\Schema;
use Qaiyo\EasyCharts\Support\Cast;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and sanitizes the global settings option.
 */
final class Settings {

	/**
	 * Cache.
	 *
	 * @var array<string,mixed>|null
	 */
	private static $cache = null;

	/**
	 * Defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			'animate'             => true,
			'lazy'                => true,
			'hover_focus'         => true,
			'data_table'          => true,
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

	/**
	 * Forget the cached settings after they change.
	 */
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

		foreach ( array( 'animate', 'lazy', 'hover_focus', 'data_table', 'delete_on_uninstall' ) as $flag ) {
			$clean[ $flag ] = array_key_exists( $flag, $input ) ? Cast::bool( $input[ $flag ] ) : ( empty( $input ) ? $defaults[ $flag ] : false );
		}

		return $clean;
	}
}
