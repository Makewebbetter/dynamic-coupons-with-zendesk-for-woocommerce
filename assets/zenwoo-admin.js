(function( $ ) {
	'use strict';
	// zenwoo_ajax_object variables.
	var ajaxUrl               = zenwoo_ajax_object.ajax_url;
	var zenwooSecurity        = zenwoo_ajax_object.zenwooSecurity;
	var zenwooMailSuccess     = zenwoo_ajax_object.zenwooMailSuccess;
	var zenwooMailFailure     = zenwoo_ajax_object.zenwooMailFailure;
	var zenwooMailAlreadySent = zenwoo_ajax_object.zenwooMailAlreadySent;
	jQuery( document ).ready(
		function() {
				jQuery( '.mwb-coupon-reject-button' ).on(
					'click',function(){

						jQuery( '#mwb_zndsk_loader' ).show();

						jQuery.post(
							ajaxUrl , {'action' : 'mwb_zenwoo_suggest_later', 'zenwooSecurity' : zenwooSecurity }, function(response){
								location.reload();
							}
						);
					}
				);
				jQuery( '.mwb-coupon-accept-button' ).on(
					'click', function() {
						jQuery( '#mwb_zndsk_loader' ).show();
						jQuery.post(
							ajaxUrl , { 'action' : 'mwb_zenwoo_suggest_accept', 'zenwooSecurity' : zenwooSecurity}, function( response ) {

								if ( response == '"success"' ) {
									alert( zenwooMailSuccess );
									location.reload();
								} else if ( response == '"alreadySent"' ) {
									alert( zenwooMailAlreadySent );
									location.reload();
								} else {
									alert( zenwooMailFailure );
									location.reload();
								}
							}
						);
					}
				);
		}
	);
})( jQuery );
