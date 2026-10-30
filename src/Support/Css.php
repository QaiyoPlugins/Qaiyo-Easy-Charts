<?php
/**
 * Validation of user-supplied CSS values. Anything that is not clearly a
 * colour / length / font list is rejected, so a value can never break out of
 * the inline declaration it is written into.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Support;

defined( 'ABSPATH' ) || exit;

final class Css {

	/**
	 * A colour: hex, rgb[a](), hsl[a](), oklch()/oklab(), var(--x), or a keyword.
	 *
	 * @param mixed $value Any value.
	 */
	public static function color( $value ): string {
		if ( is_array( $value ) ) {
			// Bricks stores colours as arrays.
			foreach ( array( 'raw', 'rgb', 'hex' ) as $key ) {
				if ( ! empty( $value[ $key ] ) ) {
					return self::color( $value[ $key ] );
				}
			}
			return '';
		}

		$value = strtolower( trim( Cast::string( $value ) ) );
		if ( '' === $value ) {
			return '';
		}

		$patterns = array(
			'/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/',
			'/^(rgb|rgba|hsl|hsla|oklch|oklab|lab|lch)\([0-9.,%\s\/+-]+\)$/',
			'/^var\(--[a-z0-9_-]+\)$/',
			'/^[a-z]{3,20}$/',
		);
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $value ) ) {
				return $value;
			}
		}
		return '';
	}

	/**
	 * A length such as 28px, 1.5em, 40%, 12rem. Bare numbers get "px".
	 *
	 * @param mixed $value Any value.
	 */
	public static function length( $value ): string {
		$value = strtolower( trim( Cast::string( $value ) ) );
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/^\d+(\.\d+)?$/', $value ) ) {
			return $value . 'px';
		}
		return preg_match( '/^\d+(\.\d+)?(px|em|rem|%|vh|vw|svh|ch)$/', $value ) ? $value : '';
	}

	/**
	 * A font-family list (letters, digits, spaces, commas, hyphens, quotes) or a
	 * var(--preset) reference.
	 *
	 * @param mixed $value Any value.
	 */
	public static function font_family( $value ): string {
		$value = trim( Cast::string( $value ) );
		if ( '' === $value || strlen( $value ) > 200 ) {
			return '';
		}
		if ( preg_match( '/^var\(--[a-z0-9_-]+\)$/', $value ) ) {
			return $value;
		}
		return preg_match( '/^[\p{L}\p{N}\s,\'"_-]+$/u', $value ) ? $value : '';
	}

	/**
	 * Inline style string from property => value pairs; empty values skipped.
	 *
	 * @param array<string,string> $properties Already validated values.
	 */
	public static function inline( array $properties ): string {
		$style = '';
		foreach ( $properties as $property => $value ) {
			if ( '' !== $value ) {
				$style .= $property . ':' . $value . ';';
			}
		}
		return $style;
	}

	/**
	 * Locale-independent percentage for CSS.
	 */
	public static function percent( float $percent ): string {
		$number = rtrim( rtrim( sprintf( '%.4F', $percent ), '0' ), '.' );
		return ( '' === $number || '-0' === $number ? '0' : $number ) . '%';
	}
}
