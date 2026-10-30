<?php
/**
 * "Discover Qaiyo" screen.
 *
 * @package qaiyo-easy-charts
 *
 * @var list<array{name: string, description: string, icon: string, monogram: string, status: string, install_url: string, details_url: string}> $qec_cards
 * @var list<array{name: string, desc: string, url: string, monogram: string}> $qec_pro
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<div class="qae-wrap">
		<div class="qae-header">
			<h1><?php esc_html_e( 'More plugins from Qaiyo', 'qaiyo-easy-charts' ); ?></h1>
			<p class="qae-subtitle"><?php esc_html_e( 'Free plugins from the WordPress.org directory and Pro extensions from the Qaiyo ecosystem.', 'qaiyo-easy-charts' ); ?></p>
		</div>

		<h2 class="qae-section-title"><?php esc_html_e( 'Free plugins', 'qaiyo-easy-charts' ); ?></h2>
		<?php if ( array() === $qec_cards ) : ?>
			<p class="qae-subtitle"><?php esc_html_e( 'The WordPress.org plugin directory could not be reached right now. Please try again later.', 'qaiyo-easy-charts' ); ?></p>
		<?php else : ?>
			<ul class="qae-grid" role="list">
				<?php foreach ( $qec_cards as $qec_card ) : ?>
					<li class="qae-card">
						<div class="qae-card-head">
							<div class="qae-icon<?php echo '' === $qec_card['icon'] ? ' qae-icon--fallback' : ''; ?>">
								<?php if ( '' !== $qec_card['icon'] ) : ?>
									<img class="qae-icon-img" src="<?php echo esc_url( $qec_card['icon'] ); ?>" alt="" width="46" height="46" loading="lazy" />
								<?php else : ?>
									<span class="qae-icon-mono" aria-hidden="true"><?php echo esc_html( $qec_card['monogram'] ); ?></span>
								<?php endif; ?>
							</div>
							<div class="qae-badges">
								<?php if ( 'active' === $qec_card['status'] ) : ?>
									<span class="qae-badge qae-badge--active"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><?php esc_html_e( 'Active', 'qaiyo-easy-charts' ); ?></span>
								<?php elseif ( 'installed' === $qec_card['status'] ) : ?>
									<span class="qae-badge qae-badge--installed"><?php esc_html_e( 'Installed', 'qaiyo-easy-charts' ); ?></span>
								<?php endif; ?>
							</div>
						</div>
						<div class="qae-card-body">
							<h3 class="qae-name"><?php echo esc_html( $qec_card['name'] ); ?></h3>
							<?php if ( '' !== $qec_card['description'] ) : ?>
								<p class="qae-desc"><?php echo esc_html( $qec_card['description'] ); ?></p>
							<?php endif; ?>
						</div>
						<div class="qae-card-footer">
							<?php if ( '' !== $qec_card['install_url'] ) : ?>
								<a class="button button-primary qae-btn-pro" href="<?php echo esc_url( $qec_card['install_url'] ); ?>">
									<?php esc_html_e( 'Install', 'qaiyo-easy-charts' ); ?>
									<span class="screen-reader-text"><?php echo esc_html( $qec_card['name'] ); ?></span>
								</a>
							<?php endif; ?>
							<a class="button qae-btn-free" href="<?php echo esc_url( $qec_card['details_url'] ); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'Details', 'qaiyo-easy-charts' ); ?>
								<span class="screen-reader-text"><?php echo esc_html( $qec_card['name'] ); ?> <?php esc_html_e( '(opens in a new tab)', 'qaiyo-easy-charts' ); ?></span>
							</a>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<h2 class="qae-section-title"><?php esc_html_e( 'Pro extensions', 'qaiyo-easy-charts' ); ?></h2>
		<ul class="qae-grid" role="list">
			<?php foreach ( $qec_pro as $qec_item ) : ?>
				<li class="qae-card">
					<div class="qae-card-head">
						<div class="qae-icon qae-icon--fallback">
							<span class="qae-icon-mono" aria-hidden="true"><?php echo esc_html( $qec_item['monogram'] ); ?></span>
						</div>
						<div class="qae-badges"><span class="qae-badge qae-badge--pro">PRO</span></div>
					</div>
					<div class="qae-card-body">
						<h3 class="qae-name"><?php echo esc_html( $qec_item['name'] ); ?></h3>
						<p class="qae-desc"><?php echo esc_html( $qec_item['desc'] ); ?></p>
					</div>
					<div class="qae-card-footer">
						<a class="button button-primary qae-btn-pro" href="<?php echo esc_url( $qec_item['url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Learn more', 'qaiyo-easy-charts' ); ?>
							<span class="screen-reader-text"><?php echo esc_html( $qec_item['name'] ); ?> <?php esc_html_e( '(opens in a new tab)', 'qaiyo-easy-charts' ); ?></span>
						</a>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>

		<p class="qae-footer-note">
			<?php esc_html_e( 'Free plugins are listed from WordPress.org. This page makes no request to any Qaiyo server.', 'qaiyo-easy-charts' ); ?>
			<a href="https://qaiyo-plugins.com" target="_blank" rel="noopener noreferrer">qaiyo-plugins.com</a>
		</p>
	</div>
</div>
