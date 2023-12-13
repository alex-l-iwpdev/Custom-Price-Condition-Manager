/* global cpcmAdminPriceCondition, jQuery */

/**
 * @param cpcmAdminPriceCondition.ajaxUrl
 * @param cpcmAdminPriceCondition.addPriceConditionAction
 * @param cpcmAdminPriceCondition.addPriceConditionNonce
 * @param cpcmAdminPriceCondition.deletePriceConditionAction
 * @param cpcmAdminPriceCondition.deletePriceConditionNonce
 * @param cpcmAdminPriceCondition.savePriceConditionAction
 * @param cpcmAdminPriceCondition.savePriceConditionNonce
 * @param cpcmAdminPriceCondition.preloadUrl
 */
jQuery( document ).ready( function( $ ) {
	let flag = true;

	/**
	 * Add new price condition.
	 */
	const addPriceForm = $( '#ms-add-price-condition' );
	if ( addPriceForm.length ) {
		addPriceForm.click( function( e ) {
			e.preventDefault();

			const data = {
				action: cpcmAdminPriceCondition.addPriceConditionAction,
				addPriceNonce: cpcmAdminPriceCondition.addPriceConditionNonce,
				title: $( '#price-condition-title' ).val(),
				price: $( '#price-condition-price' ).val(),
				productID: $( this ).data( 'productid' ),
			};

			const preloadEl = $( this ).parent().find( '.preload' );
			const btn = $( this );
			btn.prop( 'disabled', true );

			if ( flag ) {
				$.ajax( {
					type: 'POST',
					url: cpcmAdminPriceCondition.ajaxUrl,
					data: data,
					beforeSend: function() {
						flag = false;
						preloadEl.show();
						btn.disabled = true;
					},
					success: function( res ) {
						if ( res.success ) {
							addNewRow( res.data );
						} else {
							alert( res.data.message );
						}
						$( '#price-condition-title' ).val( '' );
						$( '#price-condition-price' ).val( '' );
						flag = true;
						preloadEl.hide();
						btn.disabled = false;
					},
					error: function( xhr, status, error ) {
						console.log( 'error...', xhr );
						//error logging
						preloadEl.hide();
						btn.disabled = false;
						alert( 'Ajax Error: ' + error );
					}
				} );
			}

		} );
	}

	/**
	 * Generate new row.
	 *
	 * @param data Resp data.
	 */
	function addNewRow( data ) {
		const lengthTable = $( '#product_price_condition_tab tbody tr' ).length;
		let html = '<tr>';
		html += '<th>' + lengthTable + '</th>';
		html += '<td>' + data.title + '</td>';
		html += '<td>' + data.price + '</td>';
		html += '<td>';
		html += '<button class="button button-primary edit" data-id="' + data.id + '">Edit</button>';
		html += '<button class="button button-primary save-price" data-id="' + data.id + '">Save</button>';
		html += '<button class="button button-danger delete" data-id="' + data.id + '">Delete</button>';
		html += '<div class="preload">';
		html += '<img src="' + cpcmAdminPriceCondition.preloadUrl + '" alt="Preloader" >';
		html += '</div>';
		html += '</td>';
		html += '</tr>';

		const table = $( '#product_price_condition_tab tbody' );
		table.find( 'tr:last' ).before( html );

		deleteRowEvent( table.find( 'tr:last' ).prev().find( '.delete' ) );
		editPriceCondition( table.find( 'tr:last' ).prev().find( '.edit' ) );
		savePriceAfterEdit( table.find( 'tr:last' ).prev().find( '.save-price' ) );
	}

	/**
	 * Delete price condition.
	 */
	function deleteRowEvent( el ) {

		el.click( function( e ) {
			e.preventDefault();

			const data = {
				action: cpcmAdminPriceCondition.deletePriceConditionAction,
				deleteNonce: cpcmAdminPriceCondition.deletePriceConditionNonce,
				id: $( this ).data( 'id' ),
			};

			const preloadEl = $( this ).parent().find( '.preload' );
			const btn = $( this );
			btn.prop( 'disabled', true );

			$.ajax( {
				type: 'POST',
				url: cpcmAdminPriceCondition.ajaxUrl,
				data: data,
				beforeSend: function() {
					preloadEl.show();
					flag = false;
				},
				success: function( res ) {
					if ( res.success ) {
						deleteRow( data.id );
					} else {
						alert( res.data.message );
					}
					preloadEl.hide();
				},
				error: function( xhr, status, error ) {
					console.log( 'error...', xhr );
					//error logging
					preloadEl.hide();
					alert( 'Ajax Error: ' + error );
					flag = true;
				}
			} );

		} );
	}

	/**
	 * Delete row.
	 *
	 * @param {int} id Data attribute  item id
	 */
	function deleteRow( id ) {
		$( '.delete[data-id=' + id + ']' ).parents( 'tr' ).remove();
	}

	/**
	 * Edit condition price.
	 */
	function editPriceCondition( el ) {
		el.click( function( e ) {
			e.preventDefault();

			addEditField( $( this ).data( 'id' ) );
		} );
	}

	/**
	 * Save button.
	 *
	 * @param {jQuery} el Ellemnt.
	 *
	 * @return void
	 */
	function savePriceAfterEdit( el ) {
		el.click( function( e ) {
			e.preventDefault();

			const data = {
				action: cpcmAdminPriceCondition.savePriceConditionAction,
				saveNonce: cpcmAdminPriceCondition.savePriceConditionNonce,
				id: $( this ).data( 'id' ),
				title: $( '#edit-title' ).val(),
				price: $( '#edit-price' ).val()
			};
			const btn = $( this );
			const preloadEl = $( this ).parent().find( '.preload' );

			btn.prop( 'disabled', true );
			$.ajax( {
				type: 'POST',
				url: cpcmAdminPriceCondition.ajaxUrl,
				data: data,
				beforeSend: function() {
					preloadEl.show();
				},
				success: function( res ) {
					btn.prop( 'disabled', false );
					preloadEl.hide();
					if ( res.success ) {
						deleteEditeField( data.id, data.title, data.price );
					}
				},
				error: function( xhr, ajaxOptions, thrownError ) {
					console.log( 'error...', xhr );
					//error logging
					btn.prop( 'disabled', false );
					preloadEl.hide();
				}
			} );

		} );
	}

	/**
	 *
	 * Add Edit Fields
	 *
	 * @param {int} id Price condition id data attribute.
	 */
	function addEditField( id ) {
		const row = $( '.edit[data-id=' + id + ']' ).parents( 'tr' );

		row.find( '.save-price' ).show();
		row.find( '.edit' ).hide();

		let titleTd = row.find( 'td:first' );
		let valueTitle = titleTd.text();
		titleTd.html( '<input type="text" id="edit-title" value="' + valueTitle.trim() + '">' );

		let priceTd = row.find( 'td:first' ).next();
		let valuePrice = priceTd.text();
		priceTd.html( '<input type="number" id="edit-price" value="' + valuePrice.trim() + '">' );
	}

	/**
	 * Delete edit fields.
	 *
	 * @param {int} id Price condition id data attribute.
	 * @param {string} title Condition title.
	 * @param {float} price Price condition.
	 */
	function deleteEditeField( id, title, price ) {
		const row = $( '.save-price[data-id=' + id + ']' ).parents( 'tr' );

		row.find( '.save-price' ).hide();
		row.find( '.edit' ).show();

		row.find( 'td:first' ).text( title );
		row.find( 'td:first' ).next().text( price );

	}

	/**
	 * Add event listener endit button.
	 */
	const editBtn = $( '#product_price_condition_tab .edit' );
	if ( editBtn.length ) {
		$.each( editBtn, function( _, el ) {
			editPriceCondition( $( el ) );
		} );
	}

	/**
	 * Add event listener delete button.
	 */
	const deleteButtons = $( '#product_price_condition_tab .delete' );
	if ( deleteButtons.length ) {
		$.each( deleteButtons, function( _, el ) {
			deleteRowEvent( $( el ) );
		} );
	}

	/**
	 * Add event listener save button.
	 */
	const saveButtonsPrice = $( '#product_price_condition_tab .save-price' );
	if ( saveButtonsPrice.length ) {
		$.each( saveButtonsPrice, function( _, el ) {
			savePriceAfterEdit( $( el ) );
		} );
	}
} );
