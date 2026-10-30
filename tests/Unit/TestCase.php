<?php
/**
 * Base test case: Brain Monkey lifecycle and the WordPress functions the
 * chart code relies on.
 *
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\stubTranslationFunctions();
		Functions\stubEscapeFunctions();
		Functions\stubs(
			array(
				'get_option'          => static function ( $name, $default = false ) {
					return $default;
				},
				'sanitize_text_field' => static function ( $value ) {
					return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags_stub( (string) $value ) ) );
				},
				'sanitize_html_class' => static function ( $value ) {
					return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value );
				},
				'number_format_i18n'  => static function ( $number, $decimals = 0 ) {
					return number_format( (float) $number, (int) $decimals, '.', ',' );
				},
			)
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}

/**
 * Same behaviour as wp_strip_all_tags() for the purposes of these tests.
 */
function wp_strip_all_tags_stub( string $value ): string {
	return strip_tags( (string) preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $value ) );
}
