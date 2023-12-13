<?php
/**
 * Custom Price Condition Manager.
 *
 * @package           iwpdev/bundle-product-manager
 * @author            iwpdev
 * @license           GPL-2.0-or-later
 * @wordpress-plugin
 *
 * Plugin Name: Custom Price Condition Manager for WooCommerce.
 * Plugin URI: https://i-wp-dev.com
 * Description: Price Condition Manager" is a versatile WordPress plugin designed to simplify the management of pricing
 * conditions for your products. With this plugin, you can easily create and apply various pricing rules, discounts,
 * and special offers to enhance your e-commerce store's pricing strategy.  Boost your sales and conversion rates while
 * optimizing your pricing strategy with Price Condition Manager.
 *
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Alex Lavyhin
 * Author URI: https://profiles.wordpress.org/alexlavigin/
 * License: GPL2
 *
 * Text Domain: custom-price-condition-manager
 * Domain Path: /languages
 */

use CustomPriceConditionManager\Main;

if ( ! defined( 'ABSPATH' ) ) {
	// @codeCoverageIgnoreStart
	exit;
	// @codeCoverageIgnoreEnd
}

/**
 * Plugin version.
 */
const CPCM_VERSION = '1.0.0';

/**
 * Plugin path.
 */
const CPCM_PATH = __DIR__;

/**
 * Plugin main file
 */
const CPCM_FILE = __FILE__;

/**
 * Class autoload.
 */
require_once CPCM_PATH . '/vendor/autoload.php';

/**
 * Min ver php.
 */
const CPCM_PHP_REQUIRED_VERSION = '7.4';

/**
 * Plugin url.
 */
define( 'CPCM_URL', untrailingslashit( plugin_dir_url( CPCM_FILE ) ) );

/**
 * Access to the is_plugin_active function
 */
require_once ABSPATH . 'wp-admin/includes/plugin.php';


if ( ! function_exists( 'cpcm_is_php_version' ) ) {
	/**
	 * Check php version.
	 *
	 * @return bool
	 * @noinspection ConstantCanBeUsedInspection
	 */
	function cpcm_is_php_version(): bool {
		if ( version_compare( PHP_VERSION, CPCM_PHP_REQUIRED_VERSION, '<' ) ) {
			return false;
		}

		return true;
	}
}

if ( ! cpcm_is_php_version() ) {

	add_action(
		'admin_notices',
		[
			\CustomPriceConditionManager\Admin\Notification\Notification::class,
			'php_version_nope',
		]
	);

	if ( is_plugin_active( plugin_basename( CPCM_FILE ) ) ) {
		deactivate_plugins( plugin_basename( CPCM_FILE ) );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}

	return;
}

if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	add_action(
		'admin_notices',
		[
			\CustomPriceConditionManager\Admin\Notification\Notification::class,
			'woocommerce_no_active',
		]
	);

	if ( is_plugin_active( plugin_basename( CPCM_FILE ) ) ) {
		deactivate_plugins( plugin_basename( CPCM_FILE ) );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}

	return;
}

new Main();
