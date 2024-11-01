<?php
/**
 * ISC Short Codes
 *
 * @package     Woocommerce
 * @subpackage  Giftcards
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


function wooposgc_convert_to_isc( $atts ) {
	global $wpdb, $woocommerce;


	if ( isset( $_POST['giftcard_code'] ) )
		$giftCardNumber = sanitize_text_field( $_POST['giftcard_code'] );
 	
 	if ( is_user_logged_in() ) {
		$theButton = '<input type="submit" class="button" name="check_giftcard" value="' . __( 'Redeem Gift Card', 'wooposgc' ) . '" />';
	} else {
		$theButton = '<p style="wooposgc_redeemMessage"><a href="' . wp_login_url( get_permalink() ) . '" title="Login">' . __('Log in', 'wooposgc' ) . '</a> ' . __( 'before redeeming gift cards', 'wooposgc' ) . '.</p>';
	}


	$return = '';

	$return .= '<form class="convert_giftcard_balance" method="post">';
		$return .= '<input type="hidden" name="_wooposgc_convert_isc_nocnce" value="' . wp_create_nonce( 'convert-to-isc-nonce' ) . '">';
		$return .= '<p class="form-row form-row-first">';
			$return .= '<input type="text" name="giftcard_code" class="input-text" placeholder="' . __( 'Gift card', 'wooposgc' ) . '" id="giftcard_code" value="" />';
		$return .= '</p>';

		$return .= '<p class="form-row form-row-last">';
		$return .= $theButton;
		
		$return .= '</p>';

		$return .= '<div class="clear"></div>';
	$return .= '</form>';
	$return .= '<div id="theBalance"></div>';


	if ( isset( $_POST['giftcard_code'] ) && wp_verify_nonce( $_POST[ "_wooposgc_convert_isc_nocnce" ] , 'convert-to-isc-nonce' ) ) {

		// Check for Giftcard
		$giftcard_found = $wpdb->get_var( $wpdb->prepare( "
			SELECT $wpdb->posts.ID
			FROM $wpdb->posts
			WHERE $wpdb->posts.post_type = 'wooposgc_giftcard'
			AND $wpdb->posts.post_status = 'publish'
			AND $wpdb->posts.post_title = '%s'
		", $giftCardNumber ) );

		if ( $giftcard_found ) {
			$current_date = date("Y-m-d");
			$cardExperation = get_post_meta( $giftcard_found, 'wooposgc_expiry_date', true );

			// Valid Gift Card Entered
			if ( ( strtotime($current_date) <= strtotime($cardExperation) ) || ( strtotime($cardExperation) == '' ) ) {

				$oldBalance = get_post_meta( $giftcard_found, 'wooposgc_balance', true );
				$GiftcardBalance = (float) $oldBalance;

				$return .= '<h3>' . sprintf( __('Remaining Balance of %s moved to store credit.', 'wooposgc' ), wc_price( $GiftcardBalance ) ) . '</h3>';

				$ISC = new WOOPOSGC_ISC_Pro();
				$ISC->wooposgc_convert_giftcard ( $giftCardNumber, $GiftcardBalance );

			} else {
				$return .= '<h3>' . __('Gift Card Has Expired', 'wooposgc' ) . '</h3>';
			}
		} else {
			$return .= '<h3>' . __( 'Gift Card Does Not Exist', 'wooposgc' ) . '</h3>';

		}

		
	}

	return apply_filters( 'wooposgc_convert_to_isc', $return) ;

}
add_shortcode( 'convertgiftcardbalance', 'wooposgc_convert_to_isc' );


function wooposgc_show_isc( $atts ) {
	$atts = shortcode_atts( array(
		'title' => 'yes',
		'currency' => 'yes'
	), $atts, 'displaystorecredit' );

	$show_title = $atts['title'];
	$show_currency = $atts['currency'];

 	if ( is_user_logged_in() ) {
 		$current_user = wp_get_current_user();

 		$ISC = get_usermeta( $current_user->ID, '_wooposgc_isc', true );

		$return = '<p style="wooposgc_ISC_amount">';

		if ( $show_title <> 'none' ) 
			$return .= __( 'Store Credit: ', 'wooposgc' );
		
		if( $show_currency <> 'none' ) {
			$return .= wc_price( $ISC );
		} else {
			$return .= $ISC;
		}

		$return .= '</p>';
	}

	return apply_filters( 'wooposgc_show_isc', $return) ;
}
add_shortcode( 'displaystorecredit', 'wooposgc_show_isc' );
