<?php
/**
 * Custom Price Condition Manager main class.
 *
 * @package iwpdev/custom-price-condition-manager
 */

namespace CustomPriceConditionManager;

use CustomPriceConditionManager\Admin\PriceConditionManagerAdmin;

/**
 * Main class file.
 */
class Main {

	/**
	 * Main construct
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
		new PriceConditionManagerAdmin();
		new PriceConditionManagerFront();

		add_action( 'wp_enqueue_scripts', [ $this, 'add_script_and_style' ] );
	}

	/**
	 * Add script and style.
	 *
	 * @return void
	 */
	public function add_script_and_style(): void {
		$url = CPCM_URL;
		$min = '.min';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$min = '';
		}

		wp_enqueue_script( 'cpcm-price-condition', $url . '/assets/js/main' . $min . '.js', [ 'jquery' ], CPCM_VERSION, true );
		wp_enqueue_style( 'cpcm-price-condition', $url . '/assets/css/main' . $min . '.css', '', CPCM_VERSION );
	}
}
