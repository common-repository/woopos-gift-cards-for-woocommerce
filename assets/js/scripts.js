jQuery(document).ready(function( $ ) {
	$('#wooposgc_reload_check').change(function( ) {
		if ( $( this ).is(':checked') ) {
			$('.show-on-reload').show();
        	$('.hide-on-reload').hide();
        	$('.quantity').hide();
		} else {
			$('.show-on-reload').hide();
        	$('.hide-on-reload').show();
        	$('.quantity').show();
		}	
	});

	if ( $('#wooposgc_reload_check').is(':checked') ) {
		$('.show-on-reload').show();
    	$('.hide-on-reload').hide();
    	$('.quantity').hide();
	} else {
		$('.show-on-reload').hide();
    	$('.hide-on-reload').show();
    	$('.quantity').show();
	}

	$('#wooposgc_send_later_check').change(function( ) {
		if ( $( this ).is(':checked') ) {
			$('.show-on-send-later').show();
        	$('.hide-on-send-later').hide();
        	$('.quantity').hide();
		} else {
			$('.show-on-send-later').hide();
        	$('.hide-on-send-later').show();
        	$('.quantity').show();
		}	
	});

	if ( $('#wooposgc_send_later_check').is(':checked') ) {
		$('.show-on-send-later').show();
    	$('.hide-on-send-later').hide();
    	$('.quantity').hide();
	} else {
		$('.show-on-send-later').hide();
    	$('.hide-on-send-later').show();
    	$('.quantity').show();
	}

	$( '#wooposgc_send_later_date' ).datepicker({
		dateFormat: 'yy-mm-dd',
		numberOfMonths: 1

	});
});