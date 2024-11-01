<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


function wooposgc_check_giftcard( $atts ) {
	global $wpdb, $woocommerce;


	if ( isset( $_POST['giftcard_code'] ) )
		$giftCardNumber = sanitize_text_field( $_POST['giftcard_code'] );

	$return = '';
	$return .= '<form class="check_giftcard_balance" method="post">';

	$return .= '<p class="form-row form-row-first">';
		$return .= '<input type="text" name="giftcard_code" class="input-text" placeholder="' . __( 'Gift card', 'wooposgc' ) . '" id="giftcard_code" value="" />';
	$return .= '</p>';

	$return .= '<p class="form-row form-row-last">';
		$return .= '<input type="submit" class="button" name="check_giftcard" value="' . __( 'Check Balance', 'wooposgc' ) . '" />';
	$return .= '</p>';

	$return .= '<div class="clear"></div>';
	$return .= '</form>';
	
	$return .= '<div id="theBalance"></div>';

	if ( isset( $_POST['giftcard_code'] ) ) {

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
			$cardExperation = wooposgc_get_giftcard_expiration( $giftcard_found );

			// Valid Gift Card Entered
			if ( ( strtotime($current_date) <= strtotime($cardExperation) ) || ( strtotime($cardExperation) == '' ) ) {

				$oldBalance = wooposgc_get_giftcard_balance( $giftcard_found );
				$giftCardBalance = (float) $oldBalance;

				$return .= '<h3>' . __('Remaining Balance', 'wooposgc' ) . ': ' . wc_price( $giftCardBalance ) . '</h3>';
			} else {
				$return .= '<h3>' . __('Gift Card Has Expired', 'wooposgc' ) . '</h3>';
			}
		} else {
			$return .= '<h3>' . __( 'Gift Card Does Not Exist', 'wooposgc' ) . '</h3>';

		}

		
	}

	return apply_filters( 'wooposgc_check_giftcard', $return) ;

}
add_shortcode( 'giftcardbalance', 'wooposgc_check_giftcard' );


function wooposgc_decrease_giftcard( $atts ) {
	global $wpdb, $woocommerce;

	if( current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' )) {
		if ( isset( $_POST['giftcard_code'] ) )
			$giftCardNumber = sanitize_text_field( $_POST['giftcard_code'] );

		if ( isset( $_POST['giftcard_debt'] ) )
			$giftCardDebt = sanitize_text_field( $_POST['giftcard_debt'] );	

		$return = '';
		$return .= '<form class="check_giftcard_balance" method="post">';

		$return .= '<p class="form-row form-row-first">';
			$return .= '<input type="text" name="giftcard_code" class="input-text" placeholder="' . __( 'Gift card', 'wooposgc' ) . '" id="giftcard_code" value="" />';
		$return .= '</p>';

		$return .= '<p class="form-row form-row-first">';
			$return .= '<input type="text" name="giftcard_debt" class="input-text" placeholder="' . __( 'Amount Used', 'wooposgc' ) . '" id="giftcard_debt" value="" />';
		$return .= '</p>';

		$return .= '<p class="form-row form-row-last">';
			$return .= '<input type="submit" class="button" name="check_giftcard" value="' . __( 'Submit', 'wooposgc' ) . '" />';
		$return .= '</p>';

		$return .= '<div class="clear"></div>';
		$return .= '</form>';
		
		$return .= '<div id="theBalance"></div>';


		if ( isset( $_POST['giftcard_debt'] ) ) {

			$giftcard_found = wooposgc_get_giftcard_by_code( $giftCardNumber );

			if ( $giftcard_found ) {
				$current_date = date("Y-m-d");


				$giftcard = wooposgc_get_giftcard_info( $giftcard_found );
				$cardExperation = $giftcard['expiry_date'];

				// Valid Gift Card Entered
				if ( ( strtotime($current_date) <= strtotime($cardExperation) ) || ( strtotime($cardExperation) == '' ) ) {

					$oldBalance = $giftcard['balance'];
					$GiftcardBalance = (float) $oldBalance;

					if ( $GiftcardBalance >= $giftCardDebt ) {
						$giftcard['balance'] = (float) $GiftcardBalance - (float) $giftCardDebt;
						$giftcardRemaining = 0;
					} else {
						$giftcard['balance'] = 0;
						$giftcardRemaining = (float) $giftCardDebt - (float) $GiftcardBalance;
						$return .= '<h3>' . __('Amount Remaining to Pay', 'wooposgc' ) . ': ' . wc_price( $giftcardRemaining ) . '</h3>';
					}

					update_post_meta( $giftcard_found, '_wooposgc_giftcard', $giftcard );

					
					$return .= '<h3>' . __('Remaining Balance on Card', 'wooposgc' ) . ': ' . wc_price( $giftcard['balance'] ) . '</h3>';

				} else {
					$return .= '<h3>' . __('Gift Card Has Expired', 'wooposgc' ) . '</h3>';
				}
			} else {
				$return .= '<h3>' . __( 'Gift Card Does Not Exist', 'wooposgc' ) . '</h3>';

			}
		}

		return apply_filters( 'wooposgc_check_giftcard', $return) ;
	}
}
add_shortcode( 'giftcarddebt', 'wooposgc_decrease_giftcard' );
