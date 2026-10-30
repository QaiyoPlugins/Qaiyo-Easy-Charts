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

final class View {

	/**
	 * Echo a template. Variables must be prefixed "qec_" to keep the template
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

		foreach ( $vars as $qec_key => $qec_value ) {
			if ( is_string( $qec_key ) && preg_match( '/^qec_[a-z0-9_]+$/', $qec_key ) ) {
				${$qec_key} = $qec_value;
			}
		}
		unset( $vars, $qec_key, $qec_value );

		require $path;
	}

	private static function path( string $name ): string {
		if ( ! preg_match( '/^[a-z0-9]+(-[a-z0-9]+)*$/', $name ) ) {
			return '';
		}

		$base = realpath( QEC_PLUGIN_DIR . 'templates/admin' );
		$file = realpath( QEC_PLUGIN_DIR . 'templates/admin/' . $name . '.php' );

		if ( false === $base || false === $file || 0 !== strpos( $file, $base . DIRECTORY_SEPARATOR ) || ! is_readable( $file ) ) {
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
