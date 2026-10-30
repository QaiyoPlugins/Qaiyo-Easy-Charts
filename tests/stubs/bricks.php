<?php
/**
 * Minimal Bricks Builder stubs for static analysis (only what the plugin uses).
 *
 * @package qaiyo-easy-charts
 */

namespace Bricks;

abstract class Element {
	/** @var string */
	public $category = '';

	/** @var string */
	public $name = '';

	/** @var string */
	public $icon = '';

	/** @var string */
	public $css_selector = '';

	/** @var string[] */
	public $scripts = array();

	/** @var array<string,mixed> */
	public $settings = array();

	/** @var array<string,array<string,mixed>> */
	public $controls = array();

	/** @var array<string,array<string,mixed>> */
	public $control_groups = array();

	/**
	 * @param string|string[] $value
	 */
	public function set_attribute( string $key, string $attribute, $value = null ): void {}

	public function render_attributes( string $key ): string {
		return '';
	}

	/**
	 * @param array<string,string> $data
	 */
	public function render_element_placeholder( array $data = array() ): string {
		return '';
	}
}

class Elements {
	public static function register_element( string $file, string $name = '', string $element_class = '' ): void {}
}
