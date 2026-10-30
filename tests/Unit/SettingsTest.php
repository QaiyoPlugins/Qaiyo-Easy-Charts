<?php
/**
 * @package qaiyo-easy-charts
 */

namespace Qaiyo\EasyCharts\Tests\Unit;

use Qaiyo\EasyCharts\Admin\Settings;

final class SettingsTest extends TestCase {

	public function test_unsaved_settings_use_the_defaults(): void {
		$this->assertSame( Settings::defaults(), Settings::sanitize( array() ) );
	}

	public function test_unchecked_boxes_are_saved_as_false(): void {
		$clean = Settings::sanitize( array( 'animate' => '1' ) );

		$this->assertTrue( $clean['animate'] );
		$this->assertFalse( $clean['data_table'] );
		$this->assertFalse( $clean['delete_on_uninstall'] );
	}

	public function test_settings_hold_no_visual_options(): void {
		$this->assertSame( array( 'animate', 'lazy', 'hover_focus', 'data_table', 'delete_on_uninstall' ), array_keys( Settings::defaults() ) );
	}
}
