<?php
/**
 * "Views" meta box: how many visitors saw this chart.
 *
 * @package qaiyo-easy-charts
 *
 * @var array{total: int, week: int, month: int, days: array<string, int>} $qaiyo_ec_stats     View statistics.
 * @var bool                                                               $qaiyo_ec_enabled   Whether views are counted.
 * @var string                                                             $qaiyo_ec_reset_url Reset action URL (nonced).
 * @var bool                                                               $qaiyo_ec_was_reset Whether the counter was just reset.
 * @var string                                                             $qaiyo_ec_settings  Settings page URL.
 */

use Qaiyo\EasyCharts\Support\Cast;

defined( 'ABSPATH' ) || exit;

$qaiyo_ec_peak   = max( array_merge( array( 1 ), array_values( $qaiyo_ec_stats['days'] ) ) );
$qaiyo_ec_format = Cast::string( get_option( 'date_format' ) );
?>
<div class="qec-views">
	<?php if ( $qaiyo_ec_was_reset ) : ?>
		<p class="qec-views-notice" role="status"><?php esc_html_e( 'The view counter was reset.', 'qaiyo-easy-charts' ); ?></p>
	<?php endif; ?>

	<p class="qec-views-total">
		<strong><?php echo esc_html( number_format_i18n( $qaiyo_ec_stats['total'] ) ); ?></strong>
		<span><?php esc_html_e( 'views in total', 'qaiyo-easy-charts' ); ?></span>
	</p>

	<dl class="qec-views-periods">
		<div>
			<dt><?php esc_html_e( 'Last 7 days', 'qaiyo-easy-charts' ); ?></dt>
			<dd><?php echo esc_html( number_format_i18n( $qaiyo_ec_stats['week'] ) ); ?></dd>
		</div>
		<div>
			<dt><?php esc_html_e( 'Last 30 days', 'qaiyo-easy-charts' ); ?></dt>
			<dd><?php echo esc_html( number_format_i18n( $qaiyo_ec_stats['month'] ) ); ?></dd>
		</div>
	</dl>

	<figure class="qec-views-spark">
		<div class="qec-views-bars" aria-hidden="true">
			<?php foreach ( $qaiyo_ec_stats['days'] as $qaiyo_ec_day => $qaiyo_ec_count ) : ?>
				<span style="<?php echo esc_attr( '--qec-h:' . round( $qaiyo_ec_count / $qaiyo_ec_peak * 100 ) . '%' ); ?>" title="<?php echo esc_attr( wp_date( $qaiyo_ec_format, (int) strtotime( $qaiyo_ec_day . ' 12:00 UTC' ) ) . ': ' . number_format_i18n( $qaiyo_ec_count ) ); ?>"></span>
			<?php endforeach; ?>
		</div>
		<figcaption><?php esc_html_e( 'Views per day, last 30 days', 'qaiyo-easy-charts' ); ?></figcaption>
	</figure>

	<p class="description">
		<?php
		echo $qaiyo_ec_enabled
			? esc_html__( 'A view is counted when the chart scrolls into view, once per visitor per 30 minutes. Anonymous: no cookies, no personal data. Bots and chart editors are not counted.', 'qaiyo-easy-charts' )
			: esc_html__( 'View counting is switched off in the settings.', 'qaiyo-easy-charts' );
		?>
		<a href="<?php echo esc_url( $qaiyo_ec_settings ); ?>"><?php esc_html_e( 'Settings', 'qaiyo-easy-charts' ); ?></a>
	</p>

	<?php if ( $qaiyo_ec_stats['total'] > 0 ) : ?>
		<p><a href="<?php echo esc_url( $qaiyo_ec_reset_url ); ?>" class="button-link qec-reset-views"><?php esc_html_e( 'Reset counter', 'qaiyo-easy-charts' ); ?></a></p>
	<?php endif; ?>
</div>
