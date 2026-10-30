<?php
/**
 * Settings screen.
 *
 * @package qaiyo-easy-charts
 *
 * @var array<string,mixed>  $qaiyo_ec_settings      Current settings.
 * @var string               $qaiyo_ec_greeting_name Current user's first name.
 * @var array<string,string> $qaiyo_ec_attributes    Shortcode attribute reference.
 */

use Qaiyo\EasyCharts\Schema;

defined( 'ABSPATH' ) || exit;

$qaiyo_ec_option = Schema::OPTION_SETTINGS;

$qaiyo_ec_flags = array(
	'animate'     => __( 'Animate charts when they scroll into view', 'qaiyo-easy-charts' ),
	'hover_focus' => __( 'Highlight the hovered bar', 'qaiyo-easy-charts' ),
	'data_table'  => __( 'Add an accessible data table to every chart (screen readers, SEO, AI)', 'qaiyo-easy-charts' ),
);
?>
<div class="wrap qec-settings-wrap">
	<p class="qec-greeting">
		<?php
		/* translators: %s: current user's first name. */
		printf( esc_html__( 'Hi, %s!', 'qaiyo-easy-charts' ), esc_html( $qaiyo_ec_greeting_name ) );
		?>
		<span class="qec-wave" aria-hidden="true">&#128075;</span>
	</p>
	<h1>
		<span class="qec-brand">Qaiyo</span>
		<?php esc_html_e( 'Easy Charts settings', 'qaiyo-easy-charts' ); ?>
		<span class="qec-version">v<?php echo esc_html( QAIYO_EC_VERSION ); ?></span>
	</h1>
	<p class="qec-description"><?php esc_html_e( 'Behaviour defaults for new charts. Colors, fonts and sizes are set in each chart, block or builder element.', 'qaiyo-easy-charts' ); ?></p>

	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php settings_fields( Schema::SETTINGS_GROUP ); ?>

		<div class="qec-card">
			<h2>
				<span class="qec-panel-icon" aria-hidden="true"><span class="dashicons dashicons-admin-settings"></span></span>
				<?php esc_html_e( 'Defaults for new charts', 'qaiyo-easy-charts' ); ?>
			</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Behaviour & accessibility', 'qaiyo-easy-charts' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Behaviour & accessibility', 'qaiyo-easy-charts' ); ?></legend>
							<?php foreach ( $qaiyo_ec_flags as $qaiyo_ec_key => $qaiyo_ec_label ) : ?>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( $qaiyo_ec_option . '[' . $qaiyo_ec_key . ']' ); ?>" value="1" <?php checked( (bool) $qaiyo_ec_settings[ $qaiyo_ec_key ] ); ?> />
									<?php echo esc_html( $qaiyo_ec_label ); ?>
								</label><br />
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Uninstall', 'qaiyo-easy-charts' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $qaiyo_ec_option . '[delete_on_uninstall]' ); ?>" value="1" <?php checked( (bool) $qaiyo_ec_settings['delete_on_uninstall'] ); ?> aria-describedby="qec-set-uninstall-help" />
							<?php esc_html_e( 'Delete all charts and settings when the plugin is deleted', 'qaiyo-easy-charts' ); ?>
						</label>
						<p class="description" id="qec-set-uninstall-help"><?php esc_html_e( 'Off by default, so reinstalling keeps your charts.', 'qaiyo-easy-charts' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button(); ?>
	</form>

	<div class="qec-card">
		<h2>
			<span class="qec-panel-icon" aria-hidden="true"><span class="dashicons dashicons-shortcode"></span></span>
			<?php esc_html_e( 'Shortcode reference', 'qaiyo-easy-charts' ); ?>
		</h2>
		<p><code>[<?php echo esc_html( Schema::SHORTCODE ); ?> id="123"]</code></p>
		<p><code>[<?php echo esc_html( Schema::SHORTCODE ); ?> data="Landing page;5;;up to 5|Business site;10;15|Webshop;10;20" unit="days" orientation="vertical"]</code></p>
		<table class="widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Attribute', 'qaiyo-easy-charts' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Description', 'qaiyo-easy-charts' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $qaiyo_ec_attributes as $qaiyo_ec_attribute => $qaiyo_ec_help ) : ?>
					<tr>
						<td><code><?php echo esc_html( $qaiyo_ec_attribute ); ?></code></td>
						<td><?php echo esc_html( $qaiyo_ec_help ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
