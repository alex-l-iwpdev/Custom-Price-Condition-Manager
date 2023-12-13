<?php
/**
 * Admin notification.
 *
 * @package iwpdev/custom-price-condition-manager
 */

namespace CustomPriceConditionManager\Admin\Notification;

/**
 * Notification class file.
 */
class Notification {

	/**
	 * Incorrect PHP Version
	 *
	 * @return void
	 */
	public static function php_version_nope(): void {
		printf(
			'<div id="bpm-php-nope" class="notice notice-error is-dismissible"><p>%s</p></div>',
			wp_kses(
				sprintf(
				/* translators: 1: Required PHP version number, 2: Current PHP version number, 3: URL of PHP update help page */
					__( 'The Bundle Product Manager plugin requires PHP version %1$s or higher. This site is running PHP version %2$s. <a href="%3$s">Learn about updating PHP</a>.', 'custom-price-condition-manager' ),
					CPCM_PHP_REQUIRED_VERSION,
					PHP_VERSION,
					'https://wordpress.org/support/update-php/'
				),
				[
					'a' => [
						'href' => [],
					],
				]
			)
		);
	}

	/**
	 * Not activation WooCommerce.
	 *
	 * @return void
	 */
	public static function woocommerce_no_active(): void {
		printf(
			'<div id="bpm-woo-nope" class="notice notice-error is-dismissible"><p>%s</p></div>',
			esc_html( __( 'To activate this plugin you must first activate WooCommerce', 'custom-price-condition-manager' ) )
		);
	}
}
