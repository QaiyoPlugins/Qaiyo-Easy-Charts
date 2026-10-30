<?php
/**
 * Branded heading on the chart edit screen.
 *
 * @package qaiyo-easy-charts
 *
 * @var string $qaiyo_ec_greeting_name Current user's first name.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="qec-intro">
	<p class="qec-greeting">
		<?php
		/* translators: %s: current user's first name. */
		printf( esc_html__( 'Hi, %s!', 'qaiyo-easy-charts' ), esc_html( $qaiyo_ec_greeting_name ) );
		?>
		<span class="qec-wave" aria-hidden="true">&#128075;</span>
	</p>
	<p class="qec-brand-line">
		<span class="qec-brand">Qaiyo</span> Easy Charts <span class="qec-version">v<?php echo esc_html( QAIYO_EC_VERSION ); ?></span>
	</p>
	<p class="qec-description"><?php esc_html_e( 'Enter the bars, adjust the look, and check the live preview. Then place the chart with the shortcode, the Gutenberg block, Elementor or Bricks.', 'qaiyo-easy-charts' ); ?></p>
</div>
