jQuery( document ).ready( function( $ ) {

	/**
	 * Change price.
	 */
	const conditionPriceBtn = $( '.condition-price' );
	if ( conditionPriceBtn.length ) {
		conditionPriceBtn.click( function( e ) {
			e.preventDefault();
			setPriceConditions( $( this ) );
		} );
	}

	const activePrice = $( '.condition-price.active' );
	if ( activePrice.length ) {
		setPriceConditions( activePrice );
	}

	/**
	 * Set price condition.
	 *
	 * @param {jQuery} el Element.
	 */
	function setPriceConditions( el ) {
		$( '.condition-price' ).removeClass( 'active' );
		const oldPrice = $( '.summary.entry-summary .price, .wc-block-components-product-price:first' );
		const newPrice = $( el ).data( 'price' );
		const symbol = oldPrice.find( '.woocommerce-Price-currencySymbol' ).text().charAt( 0 );

		$( el ).addClass( 'active' );

		if ( oldPrice.find( 'del' ).length ) {
			oldPrice.find( 'ins .woocommerce-Price-amount.amount > bdi' ).html( `<span class="woocommerce-Price-currencySymbol">${symbol}</span> ${newPrice}` );
		} else {
			oldPrice.find( '.woocommerce-Price-amount.amount' ).html( `<span class="woocommerce-Price-currencySymbol">${symbol}</span> ${newPrice}` );
		}

		$( '[name=ms_price_condition]' ).val( newPrice );
		$( '[name=ms_price_condition_id]' ).val( $( el ).data( 'id' ) );
	}
} );
