<?php
/**
 * Price condition manager
 *
 * @package iwpdev/custom-price-condition-manager
 */

namespace CustomPriceConditionManager\Admin;

use CustomPriceConditionManager\Helpers\DB_Helpers;

/**
 * PriceConditionManager class file.
 */
class PriceConditionManagerAdmin {
	/**
	 * Price condition table name.
	 */
	public const CPCM_PRICE_CONDITION_TABLE_NAME = 'cpcm_price_conditions';

	/**
	 * Add new price condition action and nonce name;
	 */
	public const CPCM_PRICE_CONDITION_ADD_ACTION_NAME = 'cpcm_price_conditions_add';

	/**
	 * Delete price condition.
	 */
	public const CPCM_PRICE_CONDITION_DELETE_ACTION_NAME = 'cpcm_price_condition_delete';

	/**
	 * Save price condition after edit.
	 */
	public const CPCM_PRICE_CONDITION_SAVE_ACTION_NAME = 'cpcm_price_condition_save';

	/**
	 * PriceConditionManager construct.
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
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'add_product_price_condition_tab' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'add_product_price_condition_tab_fields' ], 9 );

		add_action( 'init', [ $this, 'add_price_condition_table' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'add_style_and_script_admin_panel' ] );

		add_action(
			'wp_ajax_' . self::CPCM_PRICE_CONDITION_ADD_ACTION_NAME,
			[
				$this,
				'add_price_condition_ajax_handler',
			]
		);
		add_action(
			'wp_ajax_nopriv_' . self::CPCM_PRICE_CONDITION_ADD_ACTION_NAME,
			[
				$this,
				'add_price_condition_ajax_handler',
			]
		);

		add_action( 'wp_ajax_' . self::CPCM_PRICE_CONDITION_DELETE_ACTION_NAME, [ $this, 'delete_price_condition' ] );
		add_action(
			'wp_ajax_nopriv_' . self::CPCM_PRICE_CONDITION_DELETE_ACTION_NAME,
			[
				$this,
				'delete_price_condition',
			]
		);

		add_action(
			'wp_ajax_' . self::CPCM_PRICE_CONDITION_SAVE_ACTION_NAME,
			[
				$this,
				'save_price_condition_ajax_handler',
			]
		);
		add_action(
			'wp_ajax_nopriv_' . self::CPCM_PRICE_CONDITION_SAVE_ACTION_NAME,
			[
				$this,
				'save_price_condition_ajax_handler',
			]
		);
	}

	/**
	 * Add style and script admin panel.
	 *
	 * @return void
	 */
	public function add_style_and_script_admin_panel(): void {
		$url = CPCM_URL;
		$min = '.min';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$min = '';
		}

		wp_enqueue_script( 'cpcm-admin-price-condition', $url . '/assets/js/admin-price-condition' . $min . '.js', [ 'jquery' ], CPCM_VERSION, true );

		wp_localize_script(
			'cpcm-admin-price-condition',
			'cpcmAdminPriceCondition',
			[
				'ajaxUrl'                    => admin_url( 'admin-ajax.php' ),
				'addPriceConditionAction'    => self::CPCM_PRICE_CONDITION_ADD_ACTION_NAME,
				'addPriceConditionNonce'     => wp_create_nonce( self::CPCM_PRICE_CONDITION_ADD_ACTION_NAME ),
				'deletePriceConditionAction' => self::CPCM_PRICE_CONDITION_DELETE_ACTION_NAME,
				'deletePriceConditionNonce'  => wp_create_nonce( self::CPCM_PRICE_CONDITION_DELETE_ACTION_NAME ),
				'savePriceConditionAction'   => self::CPCM_PRICE_CONDITION_SAVE_ACTION_NAME,
				'savePriceConditionNonce'    => wp_create_nonce( self::CPCM_PRICE_CONDITION_SAVE_ACTION_NAME ),
				'preloadUrl'                 => esc_url( $url . '/assets/img/spinner.gif' ),
			]
		);
	}

	/**
	 * Add price condition tab.
	 *
	 * @param array $tabs Tabs array.
	 *
	 * @return array
	 */
	public function add_product_price_condition_tab( array $tabs ): array {

		$tabs['price_condition'] = [
			'label'  => __( 'Price condition', 'custom-price-condition-manager' ),
			'target' => 'product_price_condition_tab',
			'class'  => [ 'show_if_simple', 'show_if_variable' ],
		];

		return $tabs;
	}

	/**
	 * Add price condition table.
	 *
	 * @return void
	 */
	public function add_price_condition_table(): void {
		$price_table = get_option( 'cpcm_price_condition_table', false );

		if ( ! $price_table ) {
			global $wpdb;

			$table_name = $wpdb->prefix . self::CPCM_PRICE_CONDITION_TABLE_NAME;
			// phpcs:disable
			$sql = "CREATE TABLE $table_name 
					   (
					       `id` BIGINT NOT NULL AUTO_INCREMENT , 
					       `product_id` BIGINT NOT NULL , 
					       `title` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL , 
					       `price` FLOAT NULL , 
					       PRIMARY KEY (`id`), 
					       INDEX (`product_id`)
					   ) ENGINE = InnoDB;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			// phpcs:enable
			add_option( 'cpcm_price_condition_table', true );
			dbDelta( $sql );
		}

	}

	/**
	 * Add product price condition tab field.
	 *
	 * @return void
	 */
	public function add_product_price_condition_tab_fields(): void {
		global $post;

		$this->get_table_style();

		$price_conditions = DB_Helpers::get_all_price_condition( $post->ID );
		?>
		<div id="product_price_condition_tab" class="panel woocommerce_options_panel">
			<div class="options_group">
				<table class="table table-striped">
					<thead>
					<tr>
						<th scope="col"><?php esc_attr_e( 'ID', 'custom-price-condition-manager' ); ?></th>
						<th scope="col"><?php esc_attr_e( 'Title', 'custom-price-condition-manager' ); ?></th>
						<th scope="col"><?php esc_attr_e( 'Price', 'custom-price-condition-manager' ); ?></th>
						<th scope="col"><?php esc_attr_e( 'Actions', 'custom-price-condition-manager' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php
					if ( ! empty( $price_conditions ) ) {
						foreach ( $price_conditions as $key => $condition ) {
							?>
							<tr>
								<th>
									<?php echo esc_html( $key + 1 ); ?>
								</th>
								<td>
									<?php echo esc_html( $condition->title ); ?>
								</td>
								<td>
									<?php echo esc_html( $condition->price ); ?>
								</td>
								<td>
									<button
											class="button button-primary edit"
											data-id="<?php echo esc_attr( $condition->id ); ?>">
										<?php esc_attr_e( 'Edit', 'custom-price-condition-manager' ); ?>
									</button>
									<button
											class="button button-primary save-price"
											data-id="<?php echo esc_attr( $condition->id ); ?>">
										<?php esc_attr_e( 'Save', 'custom-price-condition-manager' ); ?>
									</button>
									<button
											class="button button-danger delete"
											data-id="<?php echo esc_attr( $condition->id ); ?>">
										<?php esc_attr_e( 'Delete', 'custom-price-condition-manager' ); ?>
									</button>
									<div class="preload">
										<img
												src="<?php echo esc_url( CPCM_URL . '/assets/img/spinner.gif' ); ?>"
												alt="Preloader">
									</div>
								</td>
							</tr>
							<?php
						}
					}
					?>
					<tr>
						<th>
							#
						</th>
						<td>
							<label for="price-condition-title" class="hide">
								<?php esc_attr_e( 'Title', 'custom-price-condition-manager' ); ?>
							</label>
							<input
									type="text"
									id="price-condition-title"
									name="price-condition-title"
									placeholder="<?php esc_attr_e( 'Title', 'custom-price-condition-manager' ); ?>"
									value="">
						</td>
						<td>
							<label for="price-condition-price" class="hide">
								<?php esc_attr_e( 'Price', 'custom-price-condition-manager' ); ?>
							</label>
							<input
									type="number"
									id="price-condition-price"
									name="price-condition-price"
									min="0"
									placeholder="<?php esc_attr_e( 'Price', 'custom-price-condition-manager' ); ?>"
									value="">
						</td>
						<td>
							<a
									id="ms-add-price-condition"
									class="button button-primary add"
									data-productID="<?php echo esc_attr( $post->ID ); ?>"
							>
								<?php esc_attr_e( 'Add', 'custom-price-condition-manager' ); ?>
							</a>
							<div class="preload">
								<img
										src="<?php echo esc_url( CPCM_URL . '/assets/img/spinner.gif' ); ?>"
										alt="Preloader">
							</div>
						</td>
					</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Get tab style.
	 *
	 * @return void
	 */
	private function get_table_style(): void {
		?>
		<style>
			#product_price_condition_tab .preload,
			.button.button-primary.save-price {
				display: none;
			}

			.hide {
				display: none;
			}

			table {
				caption-side: bottom;
				border-collapse: collapse;
			}

			th {
				text-align: inherit;
				text-align: -webkit-match-parent;
			}

			thead,
			tbody,
			tfoot,
			tr,
			td,
			th {
				border-color: inherit;
				border-style: solid;
				border-width: 0;
			}

			.table {
				--bs-table-bg: transparent;
				--bs-table-accent-bg: transparent;
				--bs-table-striped-color: #212529;
				--bs-table-striped-bg: rgba(0, 0, 0, 0.05);
				--bs-table-active-color: #212529;
				--bs-table-active-bg: rgba(0, 0, 0, 0.1);
				--bs-table-hover-color: #212529;
				--bs-table-hover-bg: rgba(0, 0, 0, 0.075);
				width: 100%;
				margin-bottom: 1rem;
				color: #212529;
				vertical-align: top;
				border-color: #dee2e6;
			}

			.table > :not(caption) > * > * {
				padding: 0.5rem 0.5rem;
				background-color: var(--bs-table-bg);
				border-bottom-width: 1px;
				box-shadow: inset 0 0 0 9999px var(--bs-table-accent-bg);
			}

			.table > tbody {
				vertical-align: inherit;
			}

			.table > thead {
				vertical-align: bottom;
			}

			.table > :not(:last-child) > :last-child > * {
				border-bottom-color: currentColor;
			}

			.caption-top {
				caption-side: top;
			}

			.table-sm > :not(caption) > * > * {
				padding: 0.25rem 0.25rem;
			}

			.table-bordered > :not(caption) > * {
				border-width: 1px 0;
			}

			.table-bordered > :not(caption) > * > * {
				border-width: 0 1px;
			}

			.table-borderless > :not(caption) > * > * {
				border-bottom-width: 0;
			}

			.table-striped > tbody > tr:nth-of-type(odd) {
				--bs-table-accent-bg: var(--bs-table-striped-bg);
				color: var(--bs-table-striped-color);
			}

			.table-active {
				--bs-table-accent-bg: var(--bs-table-active-bg);
				color: var(--bs-table-active-color);
			}

			.table-hover > tbody > tr:hover {
				--bs-table-accent-bg: var(--bs-table-hover-bg);
				color: var(--bs-table-hover-color);
			}

			.table-primary {
				--bs-table-bg: #cfe2ff;
				--bs-table-striped-bg: #c5d7f2;
				--bs-table-striped-color: #000;
				--bs-table-active-bg: #bacbe6;
				--bs-table-active-color: #000;
				--bs-table-hover-bg: #bfd1ec;
				--bs-table-hover-color: #000;
				color: #000;
				border-color: #bacbe6;
			}

			.table-secondary {
				--bs-table-bg: #e2e3e5;
				--bs-table-striped-bg: #d7d8da;
				--bs-table-striped-color: #000;
				--bs-table-active-bg: #cbccce;
				--bs-table-active-color: #000;
				--bs-table-hover-bg: #d1d2d4;
				--bs-table-hover-color: #000;
				color: #000;
				border-color: #cbccce;
			}

			.table-success {
				--bs-table-bg: #d1e7dd;
				--bs-table-striped-bg: #c7dbd2;
				--bs-table-striped-color: #000;
				--bs-table-active-bg: #bcd0c7;
				--bs-table-active-color: #000;
				--bs-table-hover-bg: #c1d6cc;
				--bs-table-hover-color: #000;
				color: #000;
				border-color: #bcd0c7;
			}

			.table-info {
				--bs-table-bg: #cff4fc;
				--bs-table-striped-bg: #c5e8ef;
				--bs-table-striped-color: #000;
				--bs-table-active-bg: #badce3;
				--bs-table-active-color: #000;
				--bs-table-hover-bg: #bfe2e9;
				--bs-table-hover-color: #000;
				color: #000;
				border-color: #badce3;
			}

			.table-warning {
				--bs-table-bg: #fff3cd;
				--bs-table-striped-bg: #f2e7c3;
				--bs-table-striped-color: #000;
				--bs-table-active-bg: #e6dbb9;
				--bs-table-active-color: #000;
				--bs-table-hover-bg: #ece1be;
				--bs-table-hover-color: #000;
				color: #000;
				border-color: #e6dbb9;
			}

			.table-danger {
				--bs-table-bg: #f8d7da;
				--bs-table-striped-bg: #eccccf;
				--bs-table-striped-color: #000;
				--bs-table-active-bg: #dfc2c4;
				--bs-table-active-color: #000;
				--bs-table-hover-bg: #e5c7ca;
				--bs-table-hover-color: #000;
				color: #000;
				border-color: #dfc2c4;
			}

			.table-light {
				--bs-table-bg: #f8f9fa;
				--bs-table-striped-bg: #ecedee;
				--bs-table-striped-color: #000;
				--bs-table-active-bg: #dfe0e1;
				--bs-table-active-color: #000;
				--bs-table-hover-bg: #e5e6e7;
				--bs-table-hover-color: #000;
				color: #000;
				border-color: #dfe0e1;
			}

			.table-dark {
				--bs-table-bg: #212529;
				--bs-table-striped-bg: #2c3034;
				--bs-table-striped-color: #fff;
				--bs-table-active-bg: #373b3e;
				--bs-table-active-color: #fff;
				--bs-table-hover-bg: #323539;
				--bs-table-hover-color: #fff;
				color: #fff;
				border-color: #373b3e;
			}

			.table-responsive {
				overflow-x: auto;
				-webkit-overflow-scrolling: touch;
			}

			@media (max-width: 575.98px) {
				.table-responsive-sm {
					overflow-x: auto;
					-webkit-overflow-scrolling: touch;
				}
			}

			@media (max-width: 767.98px) {
				.table-responsive-md {
					overflow-x: auto;
					-webkit-overflow-scrolling: touch;
				}
			}

			@media (max-width: 991.98px) {
				.table-responsive-lg {
					overflow-x: auto;
					-webkit-overflow-scrolling: touch;
				}
			}

			@media (max-width: 1199.98px) {
				.table-responsive-xl {
					overflow-x: auto;
					-webkit-overflow-scrolling: touch;
				}
			}

			@media (max-width: 1399.98px) {
				.table-responsive-xxl {
					overflow-x: auto;
					-webkit-overflow-scrolling: touch;
				}
			}

			.d-table {
				display: table !important;
			}

			.d-table-row {
				display: table-row !important;
			}

			.d-table-cell {
				display: table-cell !important;
			}

			@media (min-width: 576px) {
				.d-sm-table {
					display: table !important;
				}

				.d-sm-table-row {
					display: table-row !important;
				}

				.d-sm-table-cell {
					display: table-cell !important;
				}
			}

			@media (min-width: 768px) {
				.d-md-table {
					display: table !important;
				}

				.d-md-table-row {
					display: table-row !important;
				}

				.d-md-table-cell {
					display: table-cell !important;
				}
			}

			@media (min-width: 992px) {
				.d-lg-table {
					display: table !important;
				}

				.d-lg-table-row {
					display: table-row !important;
				}

				.d-lg-table-cell {
					display: table-cell !important;
				}
			}

			@media (min-width: 1200px) {
				.d-xl-table {
					display: table !important;
				}

				.d-xl-table-row {
					display: table-row !important;
				}

				.d-xl-table-cell {
					display: table-cell !important;
				}
			}

			@media (min-width: 1400px) {
				.d-xxl-table {
					display: table !important;
				}

				.d-xxl-table-row {
					display: table-row !important;
				}

				.d-xxl-table-cell {
					display: table-cell !important;
				}
			}

			@media print {
				.d-print-table {
					display: table !important;
				}

				.d-print-table-row {
					display: table-row !important;
				}

				.d-print-table-cell {
					display: table-cell !important;
				}
			}
		</style>
		<?php
	}


	/**
	 * Add price condition ajax handler.
	 *
	 * @return void
	 */
	public function add_price_condition_ajax_handler(): void {
		$nonce = ! empty( $_POST['addPriceNonce'] ) ? filter_var( wp_unslash( $_POST['addPriceNonce'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : null;

		if ( ! wp_verify_nonce( $nonce, self::CPCM_PRICE_CONDITION_ADD_ACTION_NAME ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce code', 'custom-price-condition-manager' ) ] );
		}

		$product_id = ! empty( $_POST['productID'] ) ? filter_var( wp_unslash( $_POST['productID'] ), FILTER_SANITIZE_NUMBER_INT ) : null;

		if ( empty( $product_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Product ID is empty', 'custom-price-condition-manager' ) ] );
		}

		$title = ! empty( $_POST['title'] ) ? filter_var( wp_unslash( $_POST['title'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : '';

		if ( empty( $title ) ) {
			wp_send_json_error( [ 'message' => __( 'Product title is empty', 'custom-price-condition-manager' ) ] );
		}

		$price = ! empty( $_POST['price'] ) ? filter_var( wp_unslash( $_POST['price'] ), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ) : 0;

		$result = DB_Helpers::add_new_price_condition( $product_id, $title, $price );

		if ( ! empty( $result ) ) {
			wp_send_json_success( $result );
		}

		wp_send_json_error( [ 'message' => __( 'New price has not been added', 'custom-price-condition-manager' ) ] );
	}

	/**
	 * Delete price condition ajax handler.
	 *
	 * @return void
	 */
	public function delete_price_condition(): void {
		$nonce = ! empty( $_POST['deleteNonce'] ) ? filter_var( wp_unslash( $_POST['deleteNonce'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : null;

		if ( ! wp_verify_nonce( $nonce, self::CPCM_PRICE_CONDITION_DELETE_ACTION_NAME ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce code', 'custom-price-condition-manager' ) ] );
		}

		$id_condition = ! empty( $_POST['id'] ) ? filter_var( wp_unslash( $_POST['id'] ), FILTER_SANITIZE_NUMBER_INT ) : null;

		if ( empty( $id_condition ) ) {
			wp_send_json_error( [ 'message' => __( 'ID condition is empty', 'custom-price-condition-manager' ) ] );
		}

		$result = DB_Helpers::delete_price_condition( $id_condition );

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Condition not delete', 'custom-price-condition-manager' ) ] );
		}

		wp_send_json_success( [ 'message' => __( 'Successfully removed', 'custom-price-condition-manager' ) ] );
	}

	/**
	 * Save price condition after edit ajax handler.
	 *
	 * @return void
	 */
	public function save_price_condition_ajax_handler(): void {
		$nonce = ! empty( $_POST['saveNonce'] ) ? filter_var( wp_unslash( $_POST['saveNonce'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : null;

		if ( ! wp_verify_nonce( $nonce, self::CPCM_PRICE_CONDITION_SAVE_ACTION_NAME ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce code', 'custom-price-condition-manager' ) ] );
		}

		$condition_id = ! empty( $_POST['id'] ) ? filter_var( wp_unslash( $_POST['id'] ), FILTER_SANITIZE_NUMBER_INT ) : null;

		if ( empty( $condition_id ) ) {
			wp_send_json_error( [ 'message' => __( 'ID condition is empty', 'custom-price-condition-manager' ) ] );
		}

		$title = ! empty( $_POST['title'] ) ? filter_var( wp_unslash( $_POST['title'] ), FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : null;

		if ( empty( $title ) ) {
			wp_send_json_error( [ 'message' => __( 'Product title is empty', 'custom-price-condition-manager' ) ] );
		}

		$price = ! empty( $_POST['price'] ) ? filter_var( wp_unslash( $_POST['price'] ), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ) : 0;

		if ( empty( $price ) ) {
			wp_send_json_error( [ 'message' => __( 'Product price is empty', 'custom-price-condition-manager' ) ] );
		}

		$result = DB_Helpers::update_price_condition( $condition_id, $title, $price );

		if ( $result ) {
			wp_send_json_success( [ 'mesaage' => __( 'Successfully update', 'custom-price-condition-manager' ) ] );
		}

		wp_send_json_error( [ 'massage' => __( 'Condition not updated', 'custom-price-condition-manager' ) ] );
	}
}
