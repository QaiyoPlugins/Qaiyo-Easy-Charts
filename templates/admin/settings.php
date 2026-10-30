<?php
/**
 * Settings screen.
 *
 * @package qaiyo-easy-charts
 *
 * @var array<string,mixed>  $qec_settings      Current settings.
 * @var array<string,mixed>  $qec_defaults      Default settings.
 * @var string               $qec_greeting_name Current user's first name.
 * @var array<string,string> $qec_attributes    Shortcode attribute reference.
 */

use Qaiyo\EasyCharts\Schema;

defined( 'ABSPATH' ) || exit;

$qec_option = Schema::OPTION_SETTINGS;

$qec_colors = array(
	'bar_color'   => __( 'Bar color', 'qaiyo-easy-charts' ),
	'range_color' => __( 'Range color', 'qaiyo-easy-charts' ),
	'track_color' => __( 'Track color', 'qaiyo-easy-charts' ),
	'label_color' => __( 'Label color', 'qaiyo-easy-charts' ),
	'value_color' => __( 'Value color', 'qaiyo-easy-charts' ),
	'axis_color'  => __( 'Axis color', 'qaiyo-easy-charts' ),
);

$qec_flags = array(
	'animate'     => __( 'Animate charts when they scroll into view', 'qaiyo-easy-charts' ),
	'hover_focus' => __( 'Highlight the hovered bar', 'qaiyo-easy-charts' ),
	'data_table'  => __( 'Add an accessible data table to every chart (screen readers, SEO, AI)', 'qaiyo-easy-charts' ),
);
?>
<div class="wrap qec-settings-wrap">
	<p class="qec-greeting">
		<?php
		/* translators: %s: current user's first name. */
		printf( esc_html__( 'Hi, %s!', 'qaiyo-easy-charts' ), esc_html( $qec_greeting_name ) );
		?>
		<span class="qec-wave" aria-hidden="true">&#128075;</span>
	</p>
	<h1>
		<span class="qec-brand">Qaiyo</span>
		<?php esc_html_e( 'Easy Charts settings', 'qaiyo-easy-charts' ); ?>
		<span class="qec-version">v<?php echo esc_html( QEC_VERSION ); ?></span>
	</h1>
	<p class="qec-description"><?php esc_html_e( 'Site-wide defaults for every chart. Each chart, block and builder element can override them.', 'qaiyo-easy-charts' ); ?></p>

	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php settings_fields( Schema::SETTINGS_GROUP ); ?>

		<div class="qec-card">
			<h2>
				<span class="qec-panel-icon" aria-hidden="true"><span class="dashicons dashicons-art"></span></span>
				<?php esc_html_e( 'Colors & font', 'qaiyo-easy-charts' ); ?>
			</h2>
			<table class="form-table" role="presentation">
				<?php foreach ( $qec_colors as $qec_key => $qec_label ) : ?>
					<tr>
						<th scope="row"><label for="qec-set-<?php echo esc_attr( $qec_key ); ?>"><?php echo esc_html( $qec_label ); ?></label></th>
						<td>
							<input type="text" class="qec-color" id="qec-set-<?php echo esc_attr( $qec_key ); ?>"
								name="<?php echo esc_attr( $qec_option . '[' . $qec_key . ']' ); ?>"
								value="<?php echo esc_attr( (string) $qec_settings[ $qec_key ] ); ?>"
								data-default-color="<?php echo esc_attr( (string) $qec_defaults[ $qec_key ] ); ?>" />
						</td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<th scope="row"><label for="qec-set-font"><?php esc_html_e( 'Font family', 'qaiyo-easy-charts' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="qec-set-font" name="<?php echo esc_attr( $qec_option . '[font_family]' ); ?>"
							value="<?php echo esc_attr( (string) $qec_settings['font_family'] ); ?>" placeholder="DM Sans, sans-serif" aria-describedby="qec-set-font-help" />
						<p class="description" id="qec-set-font-help"><?php esc_html_e( 'Empty: charts use the font of your theme or page builder. The font itself must be loaded by your theme.', 'qaiyo-easy-charts' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="qec-card">
			<h2>
				<span class="qec-panel-icon" aria-hidden="true"><span class="dashicons dashicons-admin-settings"></span></span>
				<?php esc_html_e( 'Defaults for new charts', 'qaiyo-easy-charts' ); ?>
			</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="qec-set-orientation"><?php esc_html_e( 'Orientation', 'qaiyo-easy-charts' ); ?></label></th>
					<td>
						<select id="qec-set-orientation" name="<?php echo esc_attr( $qec_option . '[orientation]' ); ?>">
							<option value="horizontal" <?php selected( $qec_settings['orientation'], 'horizontal' ); ?>><?php esc_html_e( 'Horizontal bars', 'qaiyo-easy-charts' ); ?></option>
							<option value="vertical" <?php selected( $qec_settings['orientation'], 'vertical' ); ?>><?php esc_html_e( 'Vertical columns', 'qaiyo-easy-charts' ); ?></option>
							<option value="auto" <?php selected( $qec_settings['orientation'], 'auto' ); ?>><?php esc_html_e( 'Columns, bars on narrow screens', 'qaiyo-easy-charts' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Behaviour & accessibility', 'qaiyo-easy-charts' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Behaviour & accessibility', 'qaiyo-easy-charts' ); ?></legend>
							<?php foreach ( $qec_flags as $qec_key => $qec_label ) : ?>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( $qec_option . '[' . $qec_key . ']' ); ?>" value="1" <?php checked( (bool) $qec_settings[ $qec_key ] ); ?> />
									<?php echo esc_html( $qec_label ); ?>
								</label><br />
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Uninstall', 'qaiyo-easy-charts' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $qec_option . '[delete_on_uninstall]' ); ?>" value="1" <?php checked( (bool) $qec_settings['delete_on_uninstall'] ); ?> aria-describedby="qec-set-uninstall-help" />
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
				<?php foreach ( $qec_attributes as $qec_attribute => $qec_help ) : ?>
					<tr>
						<td><code><?php echo esc_html( $qec_attribute ); ?></code></td>
						<td><?php echo esc_html( $qec_help ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
