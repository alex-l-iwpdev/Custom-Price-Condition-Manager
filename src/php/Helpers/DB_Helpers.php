<?php
/**
 * DB Helpers.
 *
 * @package iwpdev/custom-price-condition-manager
 */

namespace CustomPriceConditionManager\Helpers;

use CustomPriceConditionManager\Admin\PriceConditionManagerAdmin;

/**
 * DB_Helpers class file.
 */
class DB_Helpers {
	/**
	 * Add new price condition.
	 *
	 * @param int    $product_id Woocommerce product id.
	 * @param string $title      Tag title.
	 * @param float  $price      Product price.
	 *
	 * @return array
	 */
	public static function add_new_price_condition( int $product_id, string $title, float $price ): array {
		global $wpdb;

		$table_name = $wpdb->prefix . PriceConditionManagerAdmin::CPCM_PRICE_CONDITION_TABLE_NAME;

		// phpcs:disable
		$result = $wpdb->insert(
			$table_name,
			[
				'product_id' => $product_id,
				'title'      => $title,
				'price'      => $price,
			],
			[
				'%d',
				'%s',
				'%f',
			]
		);
		// phpcs:enable

		if ( $result ) {
			return [
				'id'    => $wpdb->insert_id,
				'title' => $title,
				'price' => $price,
			];
		}

		return [];
	}

	/**
	 * Get all price condition
	 *
	 * @param int $product_id Woocommerce product id.
	 *
	 * @return array|\stdClass[]
	 */
	public static function get_all_price_condition( $product_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . PriceConditionManagerAdmin::CPCM_PRICE_CONDITION_TABLE_NAME;
		// phpcs:disable
		$result = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE product_id = %d", $product_id )
		);
		// phpcs:enable

		if ( ! empty( $result ) ) {
			return $result;
		}

		return [];
	}

	/**
	 * Delete price condition.
	 *
	 * @param int $condition_id Condition id.
	 *
	 * @return bool
	 */
	public static function delete_price_condition( int $condition_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . PriceConditionManagerAdmin::CPCM_PRICE_CONDITION_TABLE_NAME;
		// phpcs:disable
		$result = $wpdb->delete( $table_name, [ 'id' => $condition_id ], [ '%d' ] );
		// phpcs:enable
		if ( ! $result ) {
			return false;
		}

		return true;
	}

	/**
	 * Update price condition.
	 *
	 * @param int    $condition_id Condition id.
	 * @param string $title        Condition title.
	 * @param float  $price        Condition price.
	 *
	 * @return bool
	 */
	public static function update_price_condition( int $condition_id, string $title, float $price ): bool {
		global $wpdb;

		$table_name = $wpdb->prefix . PriceConditionManagerAdmin::CPCM_PRICE_CONDITION_TABLE_NAME;
		// phpcs:disable
		$result = $wpdb->update(
			$table_name,
			[
				'title' => $title,
				'price' => $price,
			],
			[
				'id' => $condition_id,
			],
			[
				'%s',
				'%f',
			],
			[
				'%d',
			]
		);
		// phpcs:enable
		if ( $result ) {
			return true;
		}

		return false;
	}

	/**
	 * Get price condition by id.
	 *
	 * @param int $condition_id Condition id.
	 *
	 * @return array
	 */
	public static function get_price_condition_by_id( int $condition_id ): array {
		global $wpdb;

		$table_name = $wpdb->prefix . PriceConditionManagerAdmin::CPCM_PRICE_CONDITION_TABLE_NAME;
		// phpcs:disable
		$result = $wpdb->get_row( "SELECT title FROM $table_name WHERE id = $condition_id", ARRAY_A );
		// phpcs:enable

		if ( ! empty( $result ) ) {
			return $result;
		}

		return [];
	}
}
