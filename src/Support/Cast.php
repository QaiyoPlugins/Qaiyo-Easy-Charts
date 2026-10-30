<?php
/**
 * Safe narrowing of WordPress' untyped values (options, meta, filters, input).
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Support;

defined( 'ABSPATH' ) || exit;

final class Cast {

	/**
	 * @param mixed $value Any value.
	 */
	public static function string( $value ): string {
		if ( is_string( $value ) ) {
			return $value;
		}
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * @param mixed $value Any value.
	 */
	public static function int( $value ): int {
		return is_scalar( $value ) ? (int) $value : 0;
	}

	/**
	 * Accepts 'yes', 'true', '1', 'on', true, 1 as true; everything else false.
	 *
	 * @param mixed $value Any value.
	 */
	public static function bool( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_int( $value ) || is_float( $value ) ) {
			return 0 != $value; // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual -- Numeric truthiness on purpose.
		}
		return is_string( $value ) && in_array( strtolower( trim( $value ) ), array( '1', 'yes', 'true', 'on' ), true );
	}

	/**
	 * @param mixed $value Any value.
	 * @return array<mixed>
	 */
	public static function arr( $value ): array {
		return is_array( $value ) ? $value : array();
	}

	/**
	 * A number from any builder format (string, number, comma decimal), or null.
	 *
	 * @param mixed $value Any value.
	 */
	public static function number( $value ): ?float {
		if ( is_int( $value ) || is_float( $value ) ) {
			return is_finite( (float) $value ) ? (float) $value : null;
		}
		if ( ! is_string( $value ) ) {
			return null;
		}
		$value = str_replace( array( ' ', "\xc2\xa0", ',' ), array( '', '', '.' ), trim( $value ) );
		return ( '' !== $value && is_numeric( $value ) ) ? (float) $value : null;
	}

	/**
	 * One of an allowlist, or the fallback.
	 *
	 * @param mixed    $value    Any value.
	 * @param string[] $allowed  Allowed values.
	 * @param string   $fallback Fallback.
	 */
	public static function choice( $value, array $allowed, string $fallback ): string {
		$value = self::string( $value );
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}
}
