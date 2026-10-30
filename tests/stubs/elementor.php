<?php
/**
 * Minimal Elementor stubs for static analysis (only what the plugin uses).
 *
 * @package qaiyo-easy-charts
 */

namespace {
	define( 'ELEMENTOR_VERSION', '3.25.0' );
}

namespace Elementor {

class Controls_Manager {
	const TAB_CONTENT = 'content';
	const TAB_STYLE   = 'style';
	const TEXT        = 'text';
	const NUMBER      = 'number';
	const SELECT      = 'select';
	const SWITCHER    = 'switcher';
	const COLOR       = 'color';
	const HEADING = 'heading';
	const SLIDER      = 'slider';
	const REPEATER    = 'repeater';
}

class Repeater {
	/**
	 * @param array<string,mixed> $args
	 */
	public function add_control( string $id, array $args ): void {}

	/**
	 * @return array<string,mixed>
	 */
	public function get_controls(): array {
		return array();
	}
}

class Group_Control_Typography {
	public static function get_type(): string {
		return 'typography';
	}
}

abstract class Widget_Base {
	/**
	 * @param array<string,mixed> $args
	 */
	public function start_controls_section( string $id, array $args = array() ): void {}

	public function end_controls_section(): void {}

	/**
	 * @param array<string,mixed> $args
	 */
	public function add_control( string $id, array $args ): void {}

	/**
	 * @param array<string,mixed> $args
	 */
	public function add_responsive_control( string $id, array $args ): void {}

	/**
	 * @param array<string,mixed> $args
	 */
	public function add_group_control( string $type, array $args ): void {}

	/**
	 * @return array<string,mixed>
	 */
	public function get_settings_for_display() {
		return array();
	}
}

class Editor {
	public function is_edit_mode(): bool {
		return false;
	}
}

class Plugin {
	/** @var Plugin */
	public static $instance;

	/** @var Editor */
	public $editor;
}

class Elements_Manager {
	/**
	 * @param array<string,mixed> $args
	 */
	public function add_category( string $name, array $args ): void {}
}

class Widgets_Manager {
	public function register( Widget_Base $widget ): void {}
}
}
