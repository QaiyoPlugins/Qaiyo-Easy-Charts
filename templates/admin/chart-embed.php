<?php
/**
 * "Embed" side box on the chart edit screen.
 *
 * @package qaiyo-easy-charts
 *
 * @var string $qaiyo_ec_shortcode Shortcode for this chart.
 * @var bool   $qaiyo_ec_is_saved  Whether the chart has been saved at least once.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="qec-embed">
	<?php if ( ! $qaiyo_ec_is_saved ) : ?>
		<p><?php esc_html_e( 'Save the chart to get its shortcode.', 'qaiyo-easy-charts' ); ?></p>
	<?php else : ?>
		<label for="qec-shortcode" class="qec-embed-label"><?php esc_html_e( 'Shortcode', 'qaiyo-easy-charts' ); ?></label>
		<div class="qec-copy">
			<input type="text" id="qec-shortcode" class="code" value="<?php echo esc_attr( $qaiyo_ec_shortcode ); ?>" readonly />
			<button type="button" class="button qec-copy-button" data-qec-copy="#qec-shortcode">
				<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
				<?php esc_html_e( 'Copy', 'qaiyo-easy-charts' ); ?>
			</button>
		</div>
	<?php endif; ?>
	<ul class="qec-embed-list">
		<li><strong><?php esc_html_e( 'Gutenberg:', 'qaiyo-easy-charts' ); ?></strong> <?php esc_html_e( 'add the "Qaiyo Easy Chart" block and pick this chart.', 'qaiyo-easy-charts' ); ?></li>
		<li><strong>Elementor / Bricks:</strong> <?php esc_html_e( 'add the "Qaiyo Easy Chart" element, then choose "Saved chart" as the source.', 'qaiyo-easy-charts' ); ?></li>
	</ul>
</div>
