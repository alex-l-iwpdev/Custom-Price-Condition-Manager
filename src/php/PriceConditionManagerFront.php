<?php
/**
 * Price condition manager front-end.
 *
 * @package iwpdev/custom-price-condition-manager
 */

namespace CustomPriceConditionManager;

use CustomPriceConditionManager\Helpers\DB_Helpers;
use WC_Cart;
use WC_Order;
use WC_Order_Item_Product;

/**
 * PriceConditionManagerFront class.
 */
class PriceConditionManagerFront {

	/**
	 * PriceConditionManagerFront construct.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Init hooks and actions.
	 *
	 * @return void
	 */
	private function init(): void {
		add_action( 'woocommerce_after_add_to_cart_form', [ $this, 'show_price_conditions' ], 25 );

		add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_price_condition_value' ], 10, 3 );
		add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'add_hidden_field_to_single_product_page' ] );
		add_action( 'woocommerce_before_calculate_totals', [ $this, 'modify_product_price' ] );
		add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'add_order_item_condition_price' ], 10, 4 );
		add_action( 'woocommerce_order_item_meta_end', [ $this, 'show_price_condition_in_order_page_front' ], 10, 3 );
		add_filter(
			'woocommerce_display_item_meta',
			[
				$this,
				'remove_condition_id_meta_from_display',
			],
			20,
			1
		);
		add_filter( 'woocommerce_hidden_order_itemmeta', [ $this, 'remove_condition_price_id_form_order' ] );

		add_action(
			'woocommerce_after_order_itemmeta',
			[
				$this,
				'show_price_condition_in_order_page_front',
			],
			20,
			3
		);
	}

	/**
	 * Show price conditions.
	 *
	 * @return void
	 */
	public function show_price_conditions(): void {
		global $post;

		$price_conditions = DB_Helpers::get_all_price_condition( $post->ID );

		if ( empty( $price_conditions ) ) {
			return;
		}
		$product        = wc_get_product( $post->ID );
		$price_options  = get_option( 'woocommerce_currency_settings', false );
		$decimal_places = isset( $price_options['decimal_places'] ) ? intval( $price_options['decimal_places'] ) : 2;

		$cart_items = WC()->cart->get_cart_contents();
		$active     = '';
		if ( ! empty( $cart_items ) ) {
			foreach ( $cart_items as $item ) {
				if ( $item['product_id'] === $post->ID && isset( $item['condition_id'] ) ) {
					$active = $item['condition_id'];
				}
			}
		}

		?>
		<div class="conditions">
			<h5><?php esc_attr_e( 'Conditions', 'arostore' ); ?></h5>
			<ul>
				<?php if ( 'instock' === $product->get_stock_status() ) { ?>
					<li>
						<button
								class="condition-price"
								data-price="<?php echo esc_attr( number_format( $product->get_price(), $decimal_places ) ); ?>">
							<?php esc_attr_e( 'Stock', 'arostore' ); ?>
						</button>
					</li>
				<?php } ?>
				<?php foreach ( $price_conditions as $condition ) { ?>
					<li>
						<button
								type="button"
								class="condition-price <?php echo esc_attr( $active === $condition->id ? 'active' : '' ); ?>"
								data-id="<?php echo esc_attr( $condition->id ); ?>"
								data-price="<?php echo esc_attr( number_format( $condition->price, $decimal_places ) ); ?>">
							<?php echo esc_html( $condition->title ); ?>
						</button>
					</li>
				<?php } ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Add price condition value.
	 *
	 * @param array $cart_item_data Array of other cart item data.
	 * @param int   $product_id     ID of the product added to the cart.
	 * @param int   $variation_id   Variation ID of the product added to the cart.
	 *
	 * @return array
	 */
	public function add_price_condition_value( array $cart_item_data, int $product_id, int $variation_id ): array {
		// phpcs:disable
		$new_price    = ! empty( $_POST['ms_price_condition'] ) ? filter_var( wp_unslash( $_POST['ms_price_condition'] ), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ) : null;
		$condition_id = ! empty( $_POST['ms_price_condition_id'] ) ? filter_var( wp_unslash( $_POST['ms_price_condition_id'] ), FILTER_SANITIZE_NUMBER_INT ) : null;
		// phpcs:enable

		if ( ! empty( $new_price ) && ! empty( $condition_id ) ) {
			$cart_item_data['new_price']    = $new_price;
			$cart_item_data['condition_id'] = $condition_id;
		}

		return $cart_item_data;
	}

	/**
	 * Add hidden field to single product page.
	 *
	 * @return void
	 */
	public function add_hidden_field_to_single_product_page(): void {
		global $post;

		$price_conditions = DB_Helpers::get_all_price_condition( $post->ID );

		if ( empty( $price_conditions ) ) {
			return;
		}

		echo '<input type="hidden" name="ms_price_condition" value="">';
		echo '<input type="hidden" name="ms_price_condition_id" value="">';

	}

	/**
	 * Modify product price.
	 *
	 * @param WC_Cart $cart Wc cart.
	 *
	 * @return void
	 */
	public function modify_product_price( WC_Cart $cart ): void {

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['new_price'] ) ) {
				$cart_item['data']->set_price( $cart_item['new_price'] );
			}
		}
	}

	/**
	 * Add to order condition price id.
	 *
	 * @param WC_Order_Item_Product $item          Order item.
	 * @param string                $cart_item_key Cart item key.
	 * @param array                 $values        Cart item data.
	 * @param WC_Order              $order         The order.
	 *
	 * @return void
	 */
	public function add_order_item_condition_price( WC_Order_Item_Product $item, string $cart_item_key, array $values, WC_Order $order ) {
		if ( isset( $values['condition_id'] ) ) {
			$item->update_meta_data( 'condition_id', $values['condition_id'] );
		}
	}


	/**
	 * Show price condition in order page front.
	 *
	 * @param int                                             $item_id Item id.
	 * @param WC_Order_Item_Product | \WC_Order_Item_Shipping $item    WC order item.
	 * @param WC_Order | \WC_Product_Simple                   $order   WC order.
	 *
	 * @return void
	 */
	public function show_price_condition_in_order_page_front( int $item_id, $item, $order ) {

		$ms_price_condition = $item->get_meta( 'condition_id' );
		$condition_title    = '';
		if ( ! empty( $ms_price_condition ) ) {
			$condition_title = DB_Helpers::get_price_condition_by_id( $ms_price_condition );
		}

		if ( $ms_price_condition ) {
			echo '<p><strong>' . esc_html( __( 'Price condition:', 'arostore' ) ) . '</strong> ' . esc_html( $condition_title['title'] ) . '</p>';
		}

	}

	/**
	 * Output order meta.
	 *
	 * @param string $html Html meta.
	 *
	 * @return string
	 */
	public function remove_condition_id_meta_from_display( string $html ): string {
		$pattern = '/<li>.*?<strong class="wc-item-meta-label">condition_id:<\/strong>.*?<\/li>/is';
		$html    = preg_replace( $pattern, '', $html );

		return $html;
	}

	/**
	 * Remove condition price in showed in meta.
	 *
	 * @param array $hidden Hidden array keys.
	 *
	 * @return array
	 */
	public function remove_condition_price_id_form_order( array $hidden ): array {
		$hidden[] = 'condition_id';

		return $hidden;
	}
}
