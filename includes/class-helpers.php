<?php
/**
 * Small shared utilities.
 *
 * @package QaiyoEasyCharts
 */

namespace Qaiyo\EasyCharts;

defined( 'ABSPATH' ) || exit;

final class Helpers {

	/**
	 * Builds an HTML attribute string. Array values are joined with a space,
	 * `true` renders a bare attribute, `null`/`false`/'' are skipped.
	 */
	public static function attributes( array $attributes ) {
		$html = '';
		foreach ( $attributes as $name => $value ) {
			if ( null === $value || false === $value || '' === $value || array() === $value ) {
				continue;
			}
			if ( true === $value ) {
				$html .= ' ' . esc_attr( $name );
				continue;
			}
			if ( is_array( $value ) ) {
				$value = implode( ' ', array_filter( $value ) );
			}
			$html .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
		}
		return $html;
	}

	/**
	 * Builds an inline `style` value from a property => value map, skipping empty values.
	 */
	public static function style( array $properties ) {
		$style = '';
		foreach ( $properties as $property => $value ) {
			if ( null === $value || '' === $value ) {
				continue;
			}
			$style .= $property . ':' . $value . ';';
		}
		return $style;
	}

	/**
	 * Accepts a numeric value in any builder format (string, number, comma decimal).
	 *
	 * @return float|null
	 */
	public static function to_number( $value ) {
		if ( is_int( $value ) || is_float( $value ) ) {
			return (float) $value;
		}
		if ( ! is_string( $value ) ) {
			return null;
		}
		$value = str_replace( array( ' ', ',' ), array( '', '.' ), trim( $value ) );
		return is_numeric( $value ) ? (float) $value : null;
	}

	/**
	 * Formats a number for display using the site locale, without trailing zeros.
	 */
	public static function format_number( $number ) {
		$number = (float) $number;
		if ( floor( $number ) === $number ) {
			return number_format_i18n( $number );
		}

		global $wp_locale;
		$decimal_point = isset( $wp_locale->number_format['decimal_point'] ) ? $wp_locale->number_format['decimal_point'] : '.';

		$formatted = number_format_i18n( $number, 2 );
		$formatted = rtrim( $formatted, '0' );
		return rtrim( $formatted, $decimal_point );
	}

	/**
	 * Normalizes a color setting. Bricks stores colors as arrays, Elementor as strings.
	 */
	public static function color( $value ) {
		if ( is_array( $value ) ) {
			foreach ( array( 'raw', 'rgb', 'hex' ) as $key ) {
				if ( ! empty( $value[ $key ] ) ) {
					return self::sanitize_css_value( $value[ $key ] );
				}
			}
			return '';
		}
		return is_string( $value ) ? self::sanitize_css_value( $value ) : '';
	}

	/**
	 * Strips characters that could break out of an inline CSS declaration.
	 */
	public static function sanitize_css_value( $value ) {
		return trim( preg_replace( '/[;{}<>"\']/', '', (string) $value ) );
	}
}
