jQuery(document).ready(function($) {
	var $body = $('body');

	$body.on('click', '.showTitle', function() {

		$('#post-body-content').toggle();
	});

	$( '.date-picker' ).datepicker({
		dateFormat: 'yy-mm-dd',
		numberOfMonths: 1

	});

	$('#_wooposgc_cp').change(function( $ ) {
	    var c = this.checked ? '1.00': '';

		$('#_regular_price').val( c );
	});

});