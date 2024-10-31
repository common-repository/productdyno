(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$(document).ready(function() {

		var _pd_plugin = {
			init: function () {
				_pd_plugin.bindPDSelectTypeDD();
				_pd_plugin.bindPDCollectionDD();
				_pd_plugin.bindPDClearCacheData();
			},

			bindPDClearCacheData: function () {
				$('.pdClearAllCahceData').on('click', _pd_plugin.pdClearCacheData.bind(null, 'click'));
			},

			bindPDSelectTypeDD: function () {

				$('.pd_select_type_dd').on('change', function () {
					// get type
					var type = $(this).val();

					// Work accoring to type
					switch(type) {
						case 'product':

							// Hide collection, collection products, no access page dropdown dropdown
							$('.pd_collection_dropdown').addClass('pd_hide');
							$('.pd_collection_products_dropdown').addClass('pd_hide');
							$('.pd_no_access_page_dropdown').addClass('pd_hide');

							// Show loader div
							$('.pd-loader-div').removeClass('pd_hide');

							// Empty products dropdown
							$('.pd_products_dd').html('');

							// Prepare ajax params
							var data = {
								'action': 'pd_get_products',
							};

							// send ajax request to get products
							jQuery.post(ajaxurl, data, function(response) {
								// show products dropdown
								$('.pd_product_dropdown').removeClass('pd_hide');

								// append default option in dropdown
								$(".pd_products_dd").append("<option value=''>Select</option>");

								// loop on reponse and append products in dropdown
								jQuery.each( JSON.parse(response), function( key, value ) {
									$(".pd_products_dd").append("<option value='"+value.id+"'>" + value.name + "</option>");
								});

								// Hide loader div
								$('.pd-loader-div').addClass('pd_hide');

							}); return;

						// If type is collection
						case 'collection':

							// Hide products, collection products and no access page dropdown
							$('.pd_product_dropdown').addClass('pd_hide');
							$('.pd_collection_products_dropdown').addClass('pd_hide');
							$('.pd_no_access_page_dropdown').addClass('pd_hide');

							// Show loader div
							$('.pd-loader-div').removeClass('pd_hide');

							// Empty collections dropdown
							$('.pd_collections_dd').html('');

							// Prepare ajax params
							var data = {
								'action': 'pd_get_collections',
							};

							// send ajax request to get collections
							jQuery.post(ajaxurl, data, function(response) {
								// Show collections dropdown
								$('.pd_collection_dropdown').removeClass('pd_hide');

								// append default option in dropdown
								$(".pd_collections_dd").append("<option value=''>Select</option>");

								// loop on reponse and append collections in dropdown
								jQuery.each( JSON.parse(response), function( key, value ) {
									$(".pd_collections_dd").append("<option value='"+value.id+"'>" + value.name + "</option>");
								});

								// Hide loader div
								$('.pd-loader-div').addClass('pd_hide');

							}); return;
					}
				});
			},

			bindPDCollectionDD: function () {
				$('.pd_collections_dd').on('change', function () {
					// Get collection id
					var collection_id = $(this).val();

					// Show loader div
					$('.pd-loader-div').removeClass('pd_hide');

					// Hide collection products dropdown
					$('.pd_collection_products_dropdown').addClass('pd_hide');

					// hide no access page dropdown
					$('.pd_no_access_page_dropdown').addClass('pd_hide');

					// Empty collection products dropdown
					$('.pd_select_collection_product_dd').html('');

					// Prepare ajax params
					var data = {
						'action': 'pd_get_collection_products',
						'collection_id': collection_id
					};

					// Send ajax to get collection products
					jQuery.post(ajaxurl, data, function(response) {

						// Show collection products dropdown
						$('.pd_collection_products_dropdown').removeClass('pd_hide');

						// show no access page dropdown
						$('.pd_no_access_page_dropdown').removeClass('pd_hide');

						// append default option in dropdown
						$('.pd_select_collection_product_dd').append("<option value=''>Any</option>");

						// loop through response and append data in collection products dropdown
						jQuery.each(JSON.parse(response), function(key, value) {
							$('.pd_select_collection_product_dd').append("<option value='"+value.productID+"'>"+ value.productName +"</option>");
						});

						// Hide loader div
						$('.pd-loader-div').addClass('pd_hide');
					});
				});
			},

			pdClearCacheData: function () {
				// Show/hide loader icon
				$('.pdClearData').hide();
				$('.pdClearingCacheData').show();

				// Prepare ajax data
				var data = {
					'action': 'pd_clear_all_cache_data',
				};

				// Send ajax request
				jQuery.post(ajaxurl, data, function(response) {
					// Show/hide loader icon
					$('.pdClearData').show();
					$('.pdClearingCacheData').hide();
				});
			}
		};

		window.onload = _pd_plugin.init();
	})

})( jQuery );