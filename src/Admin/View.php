<?php
/**
 * Template renderer for templates/admin/.
 *
 * Template names are matched against a strict pattern and the resolved path is
 * verified with realpath() to stay inside the templates directory, so a caller
 * can never reach an arbitrary file through this class.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Renders admin templates.
 */
final class View {

	/**
	 * Echo a template. Variables must be prefixed "qaiyo_ec_" to keep the template
	 * scope readable and collision-free.
	 *
	 * @param string               $name Template name, e.g. "settings".
	 * @param array<string, mixed> $vars Template variables.
	 */
	public static function output( string $name, array $vars = array() ): void {
		$path = self::path( $name );
		if ( '' === $path ) {
			return;
		}

		foreach ( $vars as $qaiyo_ec_key => $qaiyo_ec_value ) {
			if ( preg_match( '/^qaiyo_ec_[a-z0-9_]+$/', $qaiyo_ec_key ) ) {
				${$qaiyo_ec_key} = $qaiyo_ec_value;
			}
		}
		unset( $vars, $qaiyo_ec_key, $qaiyo_ec_value );

		require $path;
	}

	/**
	 * Resolve a template name to a file inside templates/admin/.
	 *
	 * @param string $name Name.
	 */
	private static function path( string $name ): string {
		if ( ! preg_match( '/^[a-z0-9]+(-[a-z0-9]+)*$/', $name ) ) {
			return '';
		}

		$base = realpath( QAIYO_EC_PLUGIN_DIR . 'templates/admin' );
		$file = realpath( QAIYO_EC_PLUGIN_DIR . 'templates/admin/' . $name . '.php' );

		if ( false === $base || false === $file || ! str_starts_with( $file, $base . DIRECTORY_SEPARATOR ) || ! is_readable( $file ) ) {
			return '';
		}
		return $file;
	}

	/**
	 * First name of the current user for the greeting.
	 */
	public static function greeting_name(): string {
		$user = wp_get_current_user();
		$name = '' !== $user->first_name ? $user->first_name : $user->display_name;
		return '' !== $name ? $name : $user->user_login;
	}
}
