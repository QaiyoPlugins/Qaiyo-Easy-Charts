<?php
/**
 * Chart builder meta box: live preview, bar rows and settings.
 *
 * @package qaiyo-easy-charts
 *
 * @var array{items: list<array{label: string, value: float, max: float|string, text: string, color: string, range_color: string}>}&array<string, mixed> $qaiyo_ec_config  Current configuration.
 * @var string $qaiyo_ec_field   Form field name.
 * @var string $qaiyo_ec_preview Rendered preview (escaped by the renderer).
 */

use Qaiyo\EasyCharts\Chart\ChartConfig;
use Qaiyo\EasyCharts\Schema;
use Qaiyo\EasyCharts\Support\Cast;

defined( 'ABSPATH' ) || exit;

$qaiyo_ec_num = static function ( $value ): string {
	$value = Cast::number( $value );
	if ( null === $value ) {
		return '';
	}
	$number = rtrim( rtrim( sprintf( '%.6F', $value ), '0' ), '.' );
	return '' === $number || '-0' === $number ? '0' : $number;
};

$qaiyo_ec_name = static function ( string $key ) use ( $qaiyo_ec_field ): string {
	return $qaiyo_ec_field . '[' . $key . ']';
};

/**
 * One editable bar row.
 *
 * @param string       $index Row key in the form.
 * @param array<mixed> $item  Bar.
 */
$qaiyo_ec_row = static function ( string $index, array $item ) use ( $qaiyo_ec_field, $qaiyo_ec_num ): void {
	$base = $qaiyo_ec_field . '[items][' . $index . ']';
	?>
	<tr class="qec-row-edit">
		<td data-label="<?php esc_attr_e( 'Label', 'qaiyo-easy-charts' ); ?>">
			<input type="text" class="widefat" name="<?php echo esc_attr( $base . '[label]' ); ?>" value="<?php echo esc_attr( Cast::string( $item['label'] ) ); ?>" aria-label="<?php esc_attr_e( 'Label', 'qaiyo-easy-charts' ); ?>" />
		</td>
		<td data-label="<?php esc_attr_e( 'Value', 'qaiyo-easy-charts' ); ?>">
			<input type="number" step="any" class="small-text" name="<?php echo esc_attr( $base . '[value]' ); ?>" value="<?php echo esc_attr( $qaiyo_ec_num( $item['value'] ) ); ?>" aria-label="<?php esc_attr_e( 'Value', 'qaiyo-easy-charts' ); ?>" />
		</td>
		<td data-label="<?php esc_attr_e( 'Range end', 'qaiyo-easy-charts' ); ?>">
			<input type="number" step="any" class="small-text" name="<?php echo esc_attr( $base . '[max]' ); ?>" value="<?php echo esc_attr( $qaiyo_ec_num( $item['max'] ) ); ?>" aria-label="<?php esc_attr_e( 'Range end', 'qaiyo-easy-charts' ); ?>" />
		</td>
		<td data-label="<?php esc_attr_e( 'Value text', 'qaiyo-easy-charts' ); ?>">
			<input type="text" class="widefat" name="<?php echo esc_attr( $base . '[text]' ); ?>" value="<?php echo esc_attr( Cast::string( $item['text'] ) ); ?>" placeholder="<?php esc_attr_e( 'Automatic', 'qaiyo-easy-charts' ); ?>" aria-label="<?php esc_attr_e( 'Value text', 'qaiyo-easy-charts' ); ?>" />
		</td>
		<td data-label="<?php esc_attr_e( 'Bar color', 'qaiyo-easy-charts' ); ?>">
			<input type="text" class="qec-color" name="<?php echo esc_attr( $base . '[color]' ); ?>" value="<?php echo esc_attr( Cast::string( $item['color'] ) ); ?>" aria-label="<?php esc_attr_e( 'Bar color', 'qaiyo-easy-charts' ); ?>" />
		</td>
		<td data-label="<?php esc_attr_e( 'Range color', 'qaiyo-easy-charts' ); ?>">
			<input type="text" class="qec-color" name="<?php echo esc_attr( $base . '[range_color]' ); ?>" value="<?php echo esc_attr( Cast::string( $item['range_color'] ) ); ?>" aria-label="<?php esc_attr_e( 'Range color', 'qaiyo-easy-charts' ); ?>" />
		</td>
		<td class="qec-row-actions">
			<button type="button" class="button-link qec-move" data-qec-move="up" aria-label="<?php esc_attr_e( 'Move bar up', 'qaiyo-easy-charts' ); ?>"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>
			<button type="button" class="button-link qec-move" data-qec-move="down" aria-label="<?php esc_attr_e( 'Move bar down', 'qaiyo-easy-charts' ); ?>"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>
			<button type="button" class="button-link qec-remove" aria-label="<?php esc_attr_e( 'Remove bar', 'qaiyo-easy-charts' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
		</td>
	</tr>
	<?php
};

$qaiyo_ec_select = static function ( string $key, string $label, array $options ) use ( $qaiyo_ec_config, $qaiyo_ec_name ): void {
	$id = 'qec-' . str_replace( '_', '-', $key );
	?>
	<div class="qec-field">
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $qaiyo_ec_name( $key ) ); ?>">
			<?php foreach ( $options as $value => $text ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( Cast::string( $qaiyo_ec_config[ $key ] ?? '' ), (string) $value ); ?>><?php echo esc_html( Cast::string( $text ) ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<?php
};

$qaiyo_ec_input = static function ( string $key, string $label, string $type = 'text', string $help = '', string $placeholder = '' ) use ( $qaiyo_ec_config, $qaiyo_ec_name, $qaiyo_ec_num ): void {
	$id    = 'qec-' . str_replace( '_', '-', $key );
	$value = 'number' === $type ? $qaiyo_ec_num( $qaiyo_ec_config[ $key ] ?? '' ) : Cast::string( $qaiyo_ec_config[ $key ] ?? '' );
	if ( '0' === $value && in_array( $key, array( 'duration', 'stagger' ), true ) ) {
		$value = ''; // 0 = the default timing, shown as the placeholder.
	}
	?>
	<div class="qec-field">
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $qaiyo_ec_name( $key ) ); ?>" value="<?php echo esc_attr( $value ); ?>"
			<?php echo 'number' === $type ? 'step="any"' : ''; ?>
			<?php echo '' !== $placeholder ? 'placeholder="' . esc_attr( $placeholder ) . '"' : ''; ?>
			<?php echo '' !== $help ? 'aria-describedby="' . esc_attr( $id . '-help' ) . '"' : ''; ?> />
		<?php if ( '' !== $help ) : ?>
			<p class="description" id="<?php echo esc_attr( $id . '-help' ); ?>"><?php echo esc_html( $help ); ?></p>
		<?php endif; ?>
	</div>
	<?php
};

$qaiyo_ec_color = static function ( string $key, string $label ) use ( $qaiyo_ec_config, $qaiyo_ec_name ): void {
	$id      = 'qec-' . str_replace( '_', '-', $key );
	$default = ChartConfig::DEFAULT_STYLE[ $key ] ?? '';
	$value   = Cast::string( $qaiyo_ec_config[ $key ] ?? '' );
	$value   = '' !== $value ? $value : $default;
	?>
	<div class="qec-field qec-field--color">
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<input type="text" class="qec-color" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $qaiyo_ec_name( $key ) ); ?>" value="<?php echo esc_attr( $value ); ?>" data-default-color="<?php echo esc_attr( $default ); ?>" />
	</div>
	<?php
};

$qaiyo_ec_check = static function ( string $key, string $label ) use ( $qaiyo_ec_config, $qaiyo_ec_name ): void {
	?>
	<div class="qec-field qec-field--check">
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $qaiyo_ec_name( $key ) ); ?>" value="1" <?php checked( (bool) $qaiyo_ec_config[ $key ] ); ?> />
			<?php echo esc_html( $label ); ?>
		</label>
	</div>
	<?php
};

wp_nonce_field( Schema::NONCE_EDITOR, 'qaiyo_ec_editor_nonce' );
?>
<div class="qec-builder">

	<section class="qec-panel qec-panel--preview" aria-labelledby="qec-preview-heading">
		<div class="qec-panel-head">
			<h2 id="qec-preview-heading" class="qec-panel-title">
				<span class="qec-panel-icon" aria-hidden="true"><span class="dashicons dashicons-visibility"></span></span>
				<?php esc_html_e( 'Live preview', 'qaiyo-easy-charts' ); ?>
			</h2>
			<button type="button" class="button qec-play"><span class="dashicons dashicons-controls-play" aria-hidden="true"></span> <?php esc_html_e( 'Play animation', 'qaiyo-easy-charts' ); ?></button>
			<div class="qec-device" role="group" aria-label="<?php esc_attr_e( 'Preview width', 'qaiyo-easy-charts' ); ?>">
				<button type="button" class="button qec-device-button" data-qec-device="desktop" aria-pressed="true"><span class="dashicons dashicons-desktop" aria-hidden="true"></span> <?php esc_html_e( 'Desktop', 'qaiyo-easy-charts' ); ?></button>
				<button type="button" class="button qec-device-button" data-qec-device="mobile" aria-pressed="false"><span class="dashicons dashicons-smartphone" aria-hidden="true"></span> <?php esc_html_e( 'Mobile', 'qaiyo-easy-charts' ); ?></button>
			</div>
		</div>
		<div class="qec-preview-stage">
			<div class="qec-preview" id="qec-preview" data-device="desktop">
				<?php echo $qaiyo_ec_preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the renderer. ?>
			</div>
		</div>
	</section>

	<section class="qec-panel" aria-labelledby="qec-bars-heading">
		<h2 id="qec-bars-heading" class="qec-panel-title">
			<span class="qec-panel-icon" aria-hidden="true"><span class="dashicons dashicons-chart-bar"></span></span>
			<?php esc_html_e( 'Bars', 'qaiyo-easy-charts' ); ?>
		</h2>
		<p class="qec-panel-desc"><?php esc_html_e( 'Value is where the solid bar ends. Range end is optional: it adds a highlighted range after the value (e.g. 10–15). Value text replaces the automatic text, e.g. "up to 5".', 'qaiyo-easy-charts' ); ?></p>

		<table class="qec-rows-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Label', 'qaiyo-easy-charts' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Value', 'qaiyo-easy-charts' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Range end', 'qaiyo-easy-charts' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Value text', 'qaiyo-easy-charts' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Bar color', 'qaiyo-easy-charts' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Range color', 'qaiyo-easy-charts' ); ?></th>
					<th scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'qaiyo-easy-charts' ); ?></span></th>
				</tr>
			</thead>
			<tbody id="qec-rows">
				<?php
				foreach ( $qaiyo_ec_config['items'] as $qaiyo_ec_index => $qaiyo_ec_item ) {
					$qaiyo_ec_row( (string) $qaiyo_ec_index, $qaiyo_ec_item );
				}
				?>
			</tbody>
		</table>
		<template id="qec-row-template">
			<?php
			$qaiyo_ec_row(
				'__i__',
				array(
					'label'       => '',
					'value'       => '',
					'max'         => '',
					'text'        => '',
					'color'       => '',
					'range_color' => '',
				)
			);
			?>
		</template>
		<p>
			<button type="button" class="button button-primary qec-add-row"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> <?php esc_html_e( 'Add bar', 'qaiyo-easy-charts' ); ?></button>
		</p>
	</section>

	<section class="qec-panel" aria-labelledby="qec-settings-heading">
		<h2 id="qec-settings-heading" class="qec-panel-title">
			<span class="qec-panel-icon" aria-hidden="true"><span class="dashicons dashicons-admin-settings"></span></span>
			<?php esc_html_e( 'Settings', 'qaiyo-easy-charts' ); ?>
		</h2>

		<div class="qec-settings-grid">
			<fieldset class="qec-fieldset">
				<legend><?php esc_html_e( 'Layout', 'qaiyo-easy-charts' ); ?></legend>
				<?php
				$qaiyo_ec_select(
					'orientation',
					__( 'Orientation', 'qaiyo-easy-charts' ),
					array(
						'horizontal' => __( 'Horizontal bars', 'qaiyo-easy-charts' ),
						'vertical'   => __( 'Vertical columns', 'qaiyo-easy-charts' ),
						'auto'       => __( 'Columns, bars on narrow screens', 'qaiyo-easy-charts' ),
					)
				);
				$qaiyo_ec_select(
					'layout',
					__( 'Labels of horizontal bars', 'qaiyo-easy-charts' ),
					array(
						'auto'    => __( 'Auto (stack when narrow)', 'qaiyo-easy-charts' ),
						'inline'  => __( 'Always in one row', 'qaiyo-easy-charts' ),
						'stacked' => __( 'Always above the bar', 'qaiyo-easy-charts' ),
					)
				);
				$qaiyo_ec_input( 'title', __( 'Title', 'qaiyo-easy-charts' ), 'text', __( 'Defaults to the chart name. Used as the accessible name of the chart.', 'qaiyo-easy-charts' ) );
				$qaiyo_ec_input( 'description', __( 'Description', 'qaiyo-easy-charts' ) );
				$qaiyo_ec_check( 'show_caption', __( 'Show title and description above the chart', 'qaiyo-easy-charts' ) );
				?>
			</fieldset>

			<fieldset class="qec-fieldset">
				<legend><?php esc_html_e( 'Scale & axis', 'qaiyo-easy-charts' ); ?></legend>
				<?php
				$qaiyo_ec_input( 'scale_min', __( 'Minimum', 'qaiyo-easy-charts' ), 'number' );
				$qaiyo_ec_input( 'scale_max', __( 'Maximum', 'qaiyo-easy-charts' ), 'number', __( 'Leave empty to calculate from the data.', 'qaiyo-easy-charts' ), __( 'Auto', 'qaiyo-easy-charts' ) );
				$qaiyo_ec_input( 'step', __( 'Axis step', 'qaiyo-easy-charts' ), 'number' );
				$qaiyo_ec_input( 'unit', __( 'Unit', 'qaiyo-easy-charts' ), 'text', '', __( 'e.g. days', 'qaiyo-easy-charts' ) );
				$qaiyo_ec_select(
					'unit_position',
					__( 'Show unit on', 'qaiyo-easy-charts' ),
					array(
						'last' => __( 'Last tick', 'qaiyo-easy-charts' ),
						'all'  => __( 'Every tick', 'qaiyo-easy-charts' ),
						'none' => __( 'Hidden', 'qaiyo-easy-charts' ),
					)
				);
				$qaiyo_ec_check( 'show_axis', __( 'Show axis', 'qaiyo-easy-charts' ) );
				?>
			</fieldset>

			<fieldset class="qec-fieldset">
				<legend><?php esc_html_e( 'Values', 'qaiyo-easy-charts' ); ?></legend>
				<?php
				$qaiyo_ec_check( 'show_values', __( 'Show values', 'qaiyo-easy-charts' ) );
				$qaiyo_ec_input( 'separator', __( 'Range separator', 'qaiyo-easy-charts' ) );
				$qaiyo_ec_input( 'prefix', __( 'Prefix', 'qaiyo-easy-charts' ) );
				$qaiyo_ec_input( 'suffix', __( 'Suffix', 'qaiyo-easy-charts' ) );
				?>
			</fieldset>

			<fieldset class="qec-fieldset">
				<legend><?php esc_html_e( 'Colors', 'qaiyo-easy-charts' ); ?></legend>
				<p class="description"><?php esc_html_e( 'These colors belong to this chart only. Single bars can have their own colors in the table above.', 'qaiyo-easy-charts' ); ?></p>
				<?php
				$qaiyo_ec_color( 'bar_color', __( 'Bar color', 'qaiyo-easy-charts' ) );
				$qaiyo_ec_color( 'range_color', __( 'Range color', 'qaiyo-easy-charts' ) );
				$qaiyo_ec_color( 'track_color', __( 'Track color', 'qaiyo-easy-charts' ) );
				$qaiyo_ec_color( 'label_color', __( 'Label color', 'qaiyo-easy-charts' ) );
				$qaiyo_ec_color( 'value_color', __( 'Value color', 'qaiyo-easy-charts' ) );
				$qaiyo_ec_color( 'axis_color', __( 'Axis color', 'qaiyo-easy-charts' ) );
				?>
			</fieldset>

			<fieldset class="qec-fieldset">
				<legend><?php esc_html_e( 'Typography & size', 'qaiyo-easy-charts' ); ?></legend>
				<?php
				$qaiyo_ec_input( 'font_family', __( 'Font family', 'qaiyo-easy-charts' ), 'text', __( 'Empty: the font of your theme or builder.', 'qaiyo-easy-charts' ), 'DM Sans, sans-serif' );
				$qaiyo_ec_input( 'bar_size', __( 'Bar thickness', 'qaiyo-easy-charts' ), 'text', '', '28px' );
				$qaiyo_ec_input( 'chart_height', __( 'Column chart height', 'qaiyo-easy-charts' ), 'text', '', '280px' );
				$qaiyo_ec_input( 'radius', __( 'Corner radius', 'qaiyo-easy-charts' ), 'text', '', '0px' );
				?>
			</fieldset>

			<fieldset class="qec-fieldset">
				<legend><?php esc_html_e( 'Behaviour & accessibility', 'qaiyo-easy-charts' ); ?></legend>
				<?php
				$qaiyo_ec_check( 'hover_focus', __( 'Highlight the hovered bar', 'qaiyo-easy-charts' ) );
				$qaiyo_ec_check( 'data_table', __( 'Add an accessible data table (screen readers, SEO, AI)', 'qaiyo-easy-charts' ) );
				$qaiyo_ec_check( 'lazy', __( 'Lazy loading: draw the chart when it scrolls near', 'qaiyo-easy-charts' ) );
				?>
			</fieldset>

			<fieldset class="qec-fieldset">
				<legend><?php esc_html_e( 'Animation', 'qaiyo-easy-charts' ); ?></legend>
				<?php
				$qaiyo_ec_check( 'animate', __( 'Animate when scrolled into view', 'qaiyo-easy-charts' ) );
				$qaiyo_ec_select(
					'animation',
					__( 'Effect', 'qaiyo-easy-charts' ),
					array(
						'grow' => __( 'Grow from the axis', 'qaiyo-easy-charts' ),
						'fade' => __( 'Fade in', 'qaiyo-easy-charts' ),
						'rise' => __( 'Rise into place', 'qaiyo-easy-charts' ),
					)
				);
				$qaiyo_ec_select(
					'easing',
					__( 'Motion curve', 'qaiyo-easy-charts' ),
					array(
						'smooth' => __( 'Smooth', 'qaiyo-easy-charts' ),
						'linear' => __( 'Even', 'qaiyo-easy-charts' ),
						'spring' => __( 'Springy', 'qaiyo-easy-charts' ),
					)
				);
				$qaiyo_ec_input( 'duration', __( 'Duration (ms)', 'qaiyo-easy-charts' ), 'number', '', '1100' );
				$qaiyo_ec_input( 'stagger', __( 'Delay between bars (ms)', 'qaiyo-easy-charts' ), 'number', '', '140' );
				$qaiyo_ec_check( 'count_up', __( 'Count the values up', 'qaiyo-easy-charts' ) );
				$qaiyo_ec_check( 'replay', __( 'Replay every time the chart scrolls into view', 'qaiyo-easy-charts' ) );
				?>
			</fieldset>
		</div>
	</section>
</div>
