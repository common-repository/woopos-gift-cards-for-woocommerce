<?php
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;



function wooposgc_createCardNumber($value) {
	global $wooposgc_giftcard_rest_make;
	if ( get_post_type() == "wooposgc_giftcard" || (isset($wooposgc_giftcard_rest_make) && $wooposgc_giftcard_rest_make)) {
		$newGift = new WOOPOSGC_Giftcard();
		$cardnumber = $newGift->generateNumber();


	    if ( empty($value) ) {
	        return $cardnumber;
	    }
	 }

	 return $value;
}
add_filter('pre_post_title', 'wooposgc_createCardNumber', 10, 3);

function wooposgc_sendGiftCard( $giftCardNumber ) {
    $giftCard = get_post_meta( $giftCardNumber, '_wooposgc_giftcard', true );

    if( ( (isset( $giftCard['sendTheEmail'] ) && $giftCard['sendTheEmail'] == 1 ) && ( isset($giftCard['balance']) && $giftCard['balance'] <> 0 ) ) ) {
        $email = new WOOPOSGC_Giftcard_Email();
        $post = get_post( $giftCardNumber );

        $email->sendEmail ( $post );

    }
}
add_action( 'wooposgc_woocommerce_after_save', 'wooposgc_sendGiftCard', 10, 2);

function  wooposgc_make_gift_card_purchasable( $purchasable, $product ) {
    $is_giftcard = get_post_meta( $product->id, '_wooposgc_isgiftcard', true );
    if ( $is_giftcard != "yes" ) $is_giftcard = get_post_meta( $product->id, 'wooposgc_giftcard', true );
    $in_stock = get_post_meta( $product->id, '_stock_status', true ) ;

	if ( ( $is_giftcard == 'yes') && ( $in_stock == "instock" ) ) {
		$purchasable = true;
	}

	return $purchasable;
}
//add_filter ( 'woocommerce_is_purchasable', 'wooposgc_make_gift_card_purchasable', 10, 2);

add_action( 'wp_loaded', 'add_filter_wooposgc_disable_coupons' );
function add_filter_wooposgc_disable_coupons(){
	add_filter( 'woocommerce_coupons_enabled', 'wooposgc_disable_coupons', 10, 1 );
}
function wooposgc_disable_coupons( $enabled ) {

	$has_giftcard = "no";
 if ( is_object( WC()->cart ) && WC()->cart->get_cart_contents_count() > 0 ) // if ( ! WC()->cart->is_empty())
           foreach ( WC()->cart->get_cart() as $key => $product) {
            $is_giftcard = get_post_meta( $product["product_id"], '_wooposgc_isgiftcard', true );
            if ( $is_giftcard != "yes" ) $is_giftcard = get_post_meta( $product["product_id"], 'wooposgc_giftcard', true );

            if ( $is_giftcard == "yes" ) {
                $has_giftcard = "yes";
            }
        }

    if ( ( get_option( 'wooposgc_woocommerce_disable_coupons') == "yes" ) && ( $has_giftcard == "yes" ) ) {
		$enabled = false;
	}

	return $enabled;
}

function wooposgc_remove_hyphens( $cardNumber ){

	$card_number = str_replace("-", "", $cardNumber);

	return $card_number;
}

