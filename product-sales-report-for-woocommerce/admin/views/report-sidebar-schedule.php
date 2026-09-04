<?php
/**
 * Report sidebar — Schedule tab (upsell / link to Scheduled Email Reports add-on).
 *
 * @package Ninjalytics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ser_coupon_code     = 'scheduledreports15';
$ser_discount_percent = '15';
$ser_product_url     = add_query_arg( 'coupon', $ser_coupon_code, 'https://berrypress.com/product/woocommerce/scheduled-email-reports/' );
$ser_active      = post_type_exists( 'wc_email_report' );
$ser_admin_url   = $ser_active ? admin_url( 'edit.php?post_type=wc_email_report' ) : '';
?>

<div class="ninjalytics-schedule">
	<header class="ninjalytics-sidebar-header">
		<div class="ninjalytics-sidebar-header-text">
			<h2 class="ninjalytics-sidebar-title"><?php esc_html_e( 'Schedule Report', 'product-sales-report-for-woocommerce' ); ?></h2>
			<p class="ninjalytics-sidebar-subtitle"><?php esc_html_e( 'Automate delivery of this report via email.', 'product-sales-report-for-woocommerce' ); ?></p>
		</div>
		<button type="button" class="ninjalytics-sidebar-close" data-ninjalytics-sidebar-close aria-label="<?php esc_attr_e( 'Close panel', 'product-sales-report-for-woocommerce' ); ?>">
			<i class="berrypress-icon-close" aria-hidden="true"></i>
		</button>
	</header>

	<div class="ninjalytics-sidebar-body ninjalytics-schedule-body">
		<?php if ( $ser_active ) : ?>
			<p class="ninjalytics-schedule-lead">
				<?php esc_html_e( 'Scheduled Email Reports is active. Create and manage recurring email deliveries from the add-on screen.', 'product-sales-report-for-woocommerce' ); ?>
			</p>
			<p class="ninjalytics-schedule-note">
				<?php esc_html_e( 'When scheduling, choose this report’s preset so the emailed file matches what you see here.', 'product-sales-report-for-woocommerce' ); ?>
			</p>
			<a href="<?php echo esc_url( $ser_admin_url ); ?>" class="berrypress-btn berrypress-btn-primary ninjalytics-schedule-cta">
				<?php esc_html_e( 'Manage scheduled reports', 'product-sales-report-for-woocommerce' ); ?>
			</a>
		<?php else : ?>
			<div class="berrypress-upgrade-box ninjalytics-schedule-upsell">
				<div>
					<i class="berrypress-icon-filled berrypress-icon-email" aria-hidden="true"></i>
					<h4><?php esc_html_e( 'Scheduled Email Reports', 'product-sales-report-for-woocommerce' ); ?></h4>
                    <p class="ninjalytics-schedule-upsell-offer-discount">
                        <?php
                        printf(
                            /* translators: %s: discount percentage */
                            esc_html__( '%s%% OFF', 'product-sales-report-for-woocommerce' ),
                            esc_html( $ser_discount_percent )
                        );
                        ?>
                    </p>
					<p class="ninjalytics-schedule-upsell-tagline">
						<?php esc_html_e( 'Send this report as an email attachment on a recurring schedule.', 'product-sales-report-for-woocommerce' ); ?>

					</p>
					<ul class="berrypress-upgrade-box-list ninjalytics-schedule-features">
						<li>
							<i class="berrypress-icon-filled berrypress-icon-check" aria-hidden="true"></i>
							<?php esc_html_e( 'Automated email delivery on daily, weekly, monthly, or custom intervals', 'product-sales-report-for-woocommerce' ); ?>
						</li>
						<li>
							<i class="berrypress-icon-filled berrypress-icon-check" aria-hidden="true"></i>
							<?php esc_html_e( 'Use your saved Ninjalytics presets — no need to rebuild the report', 'product-sales-report-for-woocommerce' ); ?>
						</li>
						<li>
							<i class="berrypress-icon-filled berrypress-icon-check" aria-hidden="true"></i>
							<?php esc_html_e( 'Multiple recipients, manual test sends, and pause without losing settings', 'product-sales-report-for-woocommerce' ); ?>
						</li>
					</ul>
					<a href="<?php echo esc_url( $ser_product_url ); ?>" class="berrypress-btn berrypress-btn-primary" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Get Scheduled Email Reports', 'product-sales-report-for-woocommerce' ); ?>
					</a>
                    <p class="ninjalytics-schedule-upsell-tagline">
                        <?php
                        printf(
                        /* translators: %s: coupon code */
                            esc_html__( 'Use coupon code %s at checkout.', 'product-sales-report-for-woocommerce' ),
                          '<strong>' . esc_html( $ser_coupon_code ) . '</strong>'
                        );
                        ?>
                    </p>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
