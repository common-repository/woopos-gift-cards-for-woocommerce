<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function wooposgc_add_giftcard_to_paypal( $paypal_args ) {
	global $woocommerce;

	$giftCardPayment = WC()->session->giftcard_payment;

	if ( isset( $paypal_args["custom"] ) ) {

		$custom = unserialize( $paypal_args["custom"] );
		$order = new WC_Order( $custom[0] );

		if( $giftCardPayment <> NULL ) {
			if ( ! ( get_option( 'wc_prices_include_tax' ) == 'yes' || $order->get_order_discount() > 0 || ( sizeof( $order->get_items() ) + sizeof( $order->get_fees() ) ) >= 9 ) ) {
				//$paypal_args['discount_amount_cart'] = 0;
			//} else {
				if ( isset( $paypal_args['discount_amount_cart'] ) ) {
					$paypal_args['discount_amount_cart'] = $paypal_args['discount_amount_cart'] + $giftCardPayment;
				} else { 
					$paypal_args['discount_amount_cart'] = $giftCardPayment;
				}
			}
		}
	}

	return apply_filters( 'wooposgc_send_giftcard_to_paypal', $paypal_args );
}
add_filter( 'woocommerce_paypal_args', 'wooposgc_add_giftcard_to_paypal');
