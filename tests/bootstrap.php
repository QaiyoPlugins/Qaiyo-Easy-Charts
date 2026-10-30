<?php
/**
 * PHPUnit bootstrap: unit tests run without WordPress (Brain Monkey).
 *
 * @package qaiyo-easy-charts
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once __DIR__ . '/phpstan-bootstrap.php';
