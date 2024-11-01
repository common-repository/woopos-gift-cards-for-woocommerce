<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


// Adds the Gift Card form to the checkout page so that customers can enter the gift card information
function wooposgc_cart_form() {
	
	if( get_option( 'wooposgc_enable_giftcard_cartpage' ) == "yes" ) {
		do_action( 'wooposgc_before_cart_form' );
		
		?>
		
		<div class="giftcard" style="float: left;">
			<label type="text" for="giftcard_code" style="display: none;"><?php _e( 'Giftcard', 'wooposgc' ); ?>:</label><input type="text" name="giftcard_code" class="input-text" id="giftcard_code" value="" placeholder="<?php _e( 'Gift Card', 'wooposgc' ); ?>" /><input type="submit" class="button" name="apply_giftcard" value="<?php _e( 'Apply Gift card', 'wooposgc' ); ?>" />
		</div>

		<?php
		do_action( 'wooposgc_after_cart_form' );
	}

}
add_action( 'woocommerce_cart_actions', 'wooposgc_cart_form' );


if ( ! function_exists( 'wooposgc_checkout_form' ) ) {

	/**
	 * Output the Giftcard form for the checkout.
	 * @access public
	 * @subpackage Checkout
	 * @return void
	 */
	function wooposgc_checkout_form() {

		if( get_option( 'wooposgc_enable_giftcard_checkoutpage' ) == 'yes' ){

			do_action( 'wooposgc_before_checkout_form' );

			$info_message = apply_filters( 'woocommerce_checkout_giftcard_message', __( 'Have a giftcard?', 'wooposgc' ) . ' <a href="#" class="showgiftcard">' . __( 'Click here to enter your code', 'wooposgc' ) . '</a>' );
			wc_print_notice( $info_message, 'notice' );
			?>

			<form class="checkout_giftcard" method="post" style="display:none">
				<p class="form-row form-row-first"><input type="text" name="giftcard_code" class="input-text" placeholder="<?php _e( 'Gift card', 'wooposgc' ); ?>" id="giftcard_code" value="" /></p>
				<p class="form-row form-row-last"><input type="submit" class="button" name="apply_giftcard" value="<?php _e( 'Apply Gift card', 'wooposgc' ); ?>" /></p>
				<div class="clear"></div>
			</form>

			<?php do_action( 'wooposgc_after_checkout_form' ); ?>

		<?php
		}
	}
	add_action( 'woocommerce_before_checkout_form', 'wooposgc_checkout_form', 10 );
}


//  Display the current gift card information on the cart
//  *Plan on adding ability to edit the infomration in the future
function wooposgc_display_giftcard_in_cart() {
	$cart = WC()->session->cart;
	$gift = 0;
	$card = array();

	foreach( $cart as $key => $product ) {

		if( WOOPOSGC_Giftcard::wooposgc_is_giftcard($product['product_id'] ) )
				$card[] = $product;
	}

	if( ! empty( $card ) ) {
		echo '<h6>' . __( 'Gift Cards In Cart', 'wooposgc' ) . '</h6>';
		echo '<table width="100%" class="shop_table cart">';
		echo '<thead>';
		echo '<tr><td>' . __( 'Name', 'wooposgc' ) . '</td><td>' . __( 'Email', 'wooposgc' ) . '</td><td>' . __( 'Price', 'wooposgc' ) . '</td><td>' . __( 'Note', 'wooposgc' ) . '</td></tr>';
		echo '</thead>';
		foreach( $card as $key => $information ) {
			if( WOOPOSGC_Giftcard::wooposgc_is_giftcard($information['product_id'] ) ){
				$gift += 1;

				$wooposgc_to_check 		= ( get_option( 'wooposgc_giftcard_to' ) <> NULL ? get_option( 'wooposgc_giftcard_to' ) : __('To', 'wooposgc' ) );
				$wooposgc_toEmail_check 	= ( get_option( 'wooposgc_giftcard_toEmail' ) <> NULL ? get_option( 'wooposgc_giftcard_toEmail' ) : __('To Email', 'wooposgc' )  );
				$wooposgc_note_check		= ( get_option( 'wooposgc_giftcard_note' ) <> NULL ? get_option( 'wooposgc_giftcard_note' ) : __('Note', 'wooposgc' )  );
				$wooposgc_reload_card	= ( get_option( 'wooposgc_giftcard_reload_card' ) <> NULL ? get_option( 'woocommerce_giftcard_reload' ) : __('Card Number', 'wooposgc' )  );
				
				for ( $i = 0; $i < $information["quantity"]; $i++ ) { 
					echo '<tr style="font-size: 0.8em">';

						echo '<td>';
						echo $information["variation"][$wooposgc_to_check];
						if ( isset( $information["variation"][$wooposgc_reload_card] ) ) {
							echo __( 'Reload card:', 'wooposgc') . ' ' . $information["variation"][$wooposgc_reload_card];
						}
						echo '</td>';
						
						echo '<td>';
						echo $information["variation"][$wooposgc_toEmail_check];
						echo '</td>';

						echo '<td>' . wc_price( $information["line_total"] / $information["quantity"] ) . '</td>';
						
						echo '<td>';
						if ( isset( $information["variation"][$wooposgc_toEmail_check] ) ) {
							echo $information["variation"][$wooposgc_note_check]; 
						}
						echo '</td>';
					echo '</tr>';
				}
			}
		}
		echo '</table>';
	}
}
add_action( 'woocommerce_after_cart_table', 'wooposgc_display_giftcard_in_cart' );


function woocommerce_apply_giftcard($giftcard_code) {
	global $wpdb;

	if ( !  empty( $_POST['giftcard_code'] ) ) {
		$giftcard_number = sanitize_text_field( $_POST['giftcard_code'] );
		$giftcard_id = WOOPOSGC_Giftcard::wooposgc_get_giftcard_by_code( $giftcard_number );

		if ( $giftcard_id ) {
			
			if ( ! WC()->session->giftcard_post ) {
				WC()->session->giftcard_post = array();
			}

			if ( ! in_array($giftcard_id, WC()->session->giftcard_post) ) {
				$current_date = date("Y-m-d");
				$cardExperation = wooposgc_get_giftcard_expiration( $giftcard_id );

				if ( ( strtotime($current_date) <= strtotime($cardExperation) ) || ( strtotime($cardExperation) == '' ) ) {
					if( wooposgc_get_giftcard_balance( $giftcard_id ) > 0 ) {
						
						if ( WC()->session->giftcard_post == NULL ) {
							WC()->session->giftcard_post = array( $giftcard_id );
							
						} else {
							$newCard = array( $giftcard_id );
							$currentCards = WC()->session->giftcard_post;

							WC()->session->giftcard_post = array_merge($newCard, $currentCards);
						}

						if ( get_option( "woocommerce_disable_coupons" ) == "yes" ) {
							WC()->cart->remove_coupons();
						}

						WC()->cart->calculate_totals();

						wc_add_notice(  __( 'Gift card applied successfully.', 'wooposgc' ), 'success' );

					} else {
						wc_add_notice( __( 'Gift Card does not have a balance!', 'wooposgc' ), 'error' );
					}
				} else {
					wc_add_notice( __( 'Gift Card has expired!', 'wooposgc' ), 'error' ); // Giftcard Entered has expired
				}
			} else {
				wc_add_notice( __( 'Gift Card already in the cart!', 'wooposgc' ), 'error' );  //  You already have a gift card in the cart		
			}
		} else {		
			wc_add_notice( __( 'Gift Card does not exist!', 'wooposgc' ), 'error' ); // Giftcard Entered does not exist
		}

		wc_print_notices();
		
		if ( defined('DOING_AJAX') && DOING_AJAX ) {
			die();
		}
	}
}
add_action( 'wp_ajax_woocommerce_apply_giftcard', 'woocommerce_apply_giftcard' );



function woocommerce_apply_giftcard_ajax($giftcard_code) {

	woocommerce_apply_giftcard( $giftcard_code );

	WC()->cart->calculate_totals();

}
add_action( 'wp_ajax_nopriv_woocommerce_apply_giftcard', 'woocommerce_apply_giftcard_ajax' );


function apply_cart_giftcard( ) {
	if ( isset( $_POST['giftcard_code'] ) ) 
		woocommerce_apply_giftcard( sanitize_text_field( $_POST['giftcard_code'] ) );
	
	WC()->cart->calculate_totals();

}
add_action ( 'woocommerce_before_cart', 'apply_cart_giftcard' );
add_action ( 'wooposgc_before_checkout_form', 'apply_cart_giftcard' );



/**
 * Function to add the giftcard data to the cart display on both the card page and the checkout page WC()->session->giftcard_balance
 *
 */
function wooposgc_order_giftcard( ) {
	global $woocommerce;

	if ( isset( $_GET['remove_giftcards'] ) ) {
		$newGiftCards = array();
		$usedGiftCards = WC()->session->giftcard_post;

		foreach ($usedGiftCards as $key => $giftcard) {
			if ( wooposgc_get_giftcard_number( $giftcard ) != $_GET['remove_giftcards'] ) {
				$newGiftCards[] = $giftcard;
			}
		}

		WC()->session->giftcard_post = $newGiftCards;
		WC()->cart->calculate_totals();
	}

	if ( isset( WC()->session->giftcard_post ) ) {
		if ( WC()->session->giftcard_post ){

			$giftCards = WC()->session->giftcard_post;



			$giftcard = new WOOPOSGC_Giftcard();
			$price = $giftcard->wooposgc_get_payment_amount();

			if ( is_cart() ) {
				$gotoPage = wc_get_cart_url();
			} else {
				$gotoPage = WC()->cart->get_checkout_url();	
			}

			?>
			<tr class="giftcard">
				<th><?php _e( 'Gift Card Payment', 'wooposgc' ); ?> </th>
				<td style="font-size:0.85em;">
					<?php echo wc_price( $price ); ?>
					<?php foreach ( $giftCards as $key => $giftCard) { 
						$cardNumber = wooposgc_get_giftcard_number( $giftCard );
						$cardValue  = wooposgc_get_giftcard_balance( $giftCard );

						?>

						<br /> <a href="<?php echo add_query_arg( 'remove_giftcards', $cardNumber, $gotoPage ) ?>"><small>[<?php _e( 'Remove', 'wooposgc' ); ?> <?php echo wc_price( $cardValue); ?> <?php _e( 'Gift Card', 'wooposgc' ); ?>]</small></a>
					<?php } ?>
				</td>
			</tr>
			<?php

		}
	}
}
add_action( 'woocommerce_review_order_before_order_total', 'wooposgc_order_giftcard' );
add_action( 'woocommerce_cart_totals_before_order_total', 'wooposgc_order_giftcard' );




/**
 * Updates the Gift Card and the order information when the order is processed
 *
 */
function wooposgc_update_card( $order_id ) {
	global $woocommerce;

	$giftCards = WC()->session->giftcard_post;
	$giftcard = new WOOPOSGC_Giftcard();
	$payment = $giftcard->wooposgc_get_payment_amount();
	$payment_raw = apply_filters( 'wooposgc_convert_current_to_default', $payment );

	if ( isset( $giftCards ) ) {

	    $giftCardIDs = array();
        $giftCardPayments = array();
        $giftCardBalances = array();
		$giftCardPayments_raw = array();
        $giftCardBalances_raw = array();

		foreach ($giftCards as $key => $giftCard_id ) {

			if ( $giftCard_id != '' ) {
				//Decrease Ballance of card
				$balance = wooposgc_get_giftcard_balance( $giftCard_id );
				$balance_raw = wooposgc_get_giftcard_balance( $giftCard_id, true );

				//$giftcard->wooposgc_decrease_balance( $giftCard_id );
				$giftCard_IDs = get_post_meta ( $giftCard_id, 'wooposgc_existingOrders_id', true );
				if( empty($giftCard_IDs) || !is_array($giftCard_IDs) ){
				    $giftCard_IDs = array();
				}
				if( !in_array($order_id, $giftCard_IDs) ){
				    $giftCard_IDs[] = $order_id;
				}

				$giftCardIDs[$key] = $giftCard_id;

				if ( $payment > $balance ){
					$giftCardPayments[$key] = $balance;
					$giftCardPayments_raw[$key] = $balance_raw;
					$newBalance = 0;
					$newBalance_raw = 0;
					$payment -= $balance;
					$payment_raw -= $balance_raw;
				} else {
					$giftCardPayments[$key] = $payment;
					$giftCardPayments_raw[$key] = $payment_raw;
					$newBalance = $balance - $payment;
					$newBalance_raw = $balance_raw - $payment_raw;
					$payment = 0;
					$payment_raw = 0;
				}
				
				
				if ( get_option( 'wooposgc_enable_one_time_use' ) == 'yes' ){
					$newBalance = $newBalance_raw = (string) 0;
				}

				$giftCardBalances[$key] = $newBalance;
				$giftCardBalances_raw[$key] = $newBalance_raw;

				//$newBalance = wooposgc_get_giftcard_balance( $giftCard_id );

				$giftCardInfo = get_post_meta( $giftCard_id, '_wooposgc_giftcard', true );

				$giftCardInfo['balance'] = $newBalance_raw;

				update_post_meta( $giftCard_id, '_wooposgc_giftcard', $giftCardInfo ); // Update balance of Giftcard
				update_post_meta( $giftCard_id, 'wooposgc_balance', $newBalance_raw );
				update_post_meta( $giftCard_id, 'wooposgc_existingOrders_id', $giftCard_IDs ); // Saves order id to gifctard post
				// Check if the gift card ballance is 0 and if it is change the post status to zerobalance
				if( $newBalance_raw == 0 ) {
					wooposgc_update_giftcard_status( $giftCard_id, 'zerobalance' );
				}

				WC()->session->idForEmail = $order_id;
			}
		}

		update_post_meta( $order_id, 'wooposgc_id', $giftCardIDs );
        update_post_meta( $order_id, 'wooposgc_payment', $giftCardPayments );
        update_post_meta( $order_id, 'wooposgc_cardnumber', wooposgc_get_order_card_numbers($order_id));
        update_post_meta( $order_id, 'wooposgc_balance', $giftCardBalances );
		if( isset($GLOBALS['WOOCS']) ){
			update_post_meta( $order_id, 'wooposgc_currency', $GLOBALS['WOOCS']->current_currency ?? $GLOBALS['WOOCS']->default_currency );
			update_post_meta( $order_id, 'wooposgc_default_currency', $GLOBALS['WOOCS']->default_currency );
			update_post_meta( $order_id, 'wooposgc_payment_raw', $giftCardPayments_raw );
			update_post_meta( $order_id, 'wooposgc_balance_raw', $giftCardBalances_raw );
			
		}
	
		unset( WC()->session->giftcard_payment, WC()->session->giftcard_post );
	}

	if ( isset ( WC()->session->giftcard_data ) ) {
		update_post_meta( $order_id, 'wooposgc_data', WC()->session->giftcard_data );

		unset( WC()->session->giftcard_data );
	}

}
add_action( 'woocommerce_checkout_order_processed', 'wooposgc_update_card' );



/**
 * Displays the giftcard data on the order thank you page
 *
 */
function wooposgc_display_giftcard( $order ) {

	$theIDNums =  get_post_meta( $order->get_id(), 'wooposgc_id', true );
	$theBalance = get_post_meta( $order->get_id(), 'wooposgc_balance', true );
	
	if ( $theIDNums ) {
		?>


		<h4 style="margin-bottom: 10px;"><?php _e( 'Gift Card Balance After Order:', 'wooposgc' ); ?></h4>
		<ul style="list-style:none; margin-left: 10px;">
		<?php
		foreach ($theIDNums as $key => $theIDNum) {
			if( isset( $theIDNum ) ) {
				if ( $theIDNum <> '' ) {
				?>
					<li><?php _e( 'Gift Card', 'wooposgc' ); ?> <?php echo wooposgc_get_giftcard_number( $theIDNum );  ?>: <?php echo wc_price( $theBalance[$key] ); ?> <?php _e( 'remaining', 'wooposgc' ); ?> <?php do_action('wooposgc_after_remaining_balance', $theIDNum, $theBalance[$key] ); ?></li>

					<?php
				}	
			}
		}
		?>
		</ul>
		<?php
		$theGiftCardData = get_post_meta( $order->get_id(), 'wooposgc_data', true );
		if( isset( $theGiftCardData ) ) {
			if ( $theGiftCardData <> '' ) {
		?>
				<h4><?php _e( 'Gift Card Information:', 'wooposgc' ); ?></h4>
				<?php
				$i = 1;

				foreach ( $theGiftCardData as $giftcard ) {

					if ( $i % 2 ) echo '<div style="margin-bottom: 10px;">';
					echo '<div style="float: left; width: 45%; margin-right: 2%;>';
					echo '<h6><strong> ' . __('Giftcard',  'wooposgc' ) . ' ' . $i . '</strong></h6>';
					echo '<ul style="font-size: 0.85em; list-style: none outside none;">';
					if ( $giftcard[wooposgc_product_num] ) 	echo '<li>' . __('Card', 'wooposgc') . ': ' . get_the_title( $giftcard[wooposgc_product_num] ) . '</li>';
					if ( $giftcard[wooposgc_to] ) 			echo '<li>' . __('To',  'wooposgc' ) . ': ' . $giftcard[wooposgc_to] . '</li>';
					if ( $giftcard[wooposgc_to_email] ) 	echo '<li>' . __('Send To',  'wooposgc' ) . ': ' . $giftcard[wooposgc_to_email] . '</li>';
					if ( $giftcard[wooposgc_balance] ) 		echo '<li>' . __('Balance',  'wooposgc' ) . ': ' . wc_price( $giftcard[wooposgc_balance] ) . '</li>';
					if ( $giftcard[wooposgc_note] ) 		echo '<li>' . __('Note',  'wooposgc' ) . ': ' . $giftcard[wooposgc_note] . '</li>';
					if ( $giftcard[wooposgc_quantity] ) 	echo '<li>' . __('Quantity',  'wooposgc' ) . ': ' . $giftcard[wooposgc_quantity] . '</li>';
					echo '</ul>';
					echo '</div>';
					if ( !( $i % 2 ) ) echo '</div>';
					$i++;
				}
				echo '<div class="clear"></div>';
			}
		}
	}
}
add_action( 'woocommerce_order_details_after_order_table', 'wooposgc_display_giftcard' );
add_action( 'woocommerce_email_after_order_table', 'wooposgc_display_giftcard' );


// NEED TO FIGURE THIS PART OUT
function wooposgc_add_order_giftcard( $total_rows, $order ) {
	$return = array();

	$order_id = $order->get_id();

	$giftCardPayment = get_post_meta( $order_id, 'wooposgc_payment', true);

	if ( !empty($giftCardPayment) ) {

		$giftValue = array_sum($giftCardPayment); //$giftValue = get_post_meta( $order->get_id(), 'wooposgc_payment', true);
		$discount = get_post_meta( $order->get_id(), '_cart_discount', true);

		if( $discount == $giftValue ) {
			unset( $total_rows['discount'] );
		} elseif ( $discount > $giftValue ) {
			$total_rows['discount']['value'] = floatval($discount) - floatval($giftValue); //$total_rows['discount']['value'] = $discount - $giftValue;
		}

		foreach ($giftCardPayment as $key => $payment ) {
			$newRow['wooposgc_data' . $key ] = array(
				'label' => __( 'Gift Card Payment:', 'wooposgc' ),
				'value'	=> wc_price( -1 * $payment )
			);
		}

		if( get_option( 'wooposgc_enable_giftcard_process' ) == 'no' ){
			array_splice($total_rows, 1, 0, $newRow);	
		} else {
			array_splice($total_rows, 2, 0, $newRow);
		}
	}

	return $total_rows;
}
add_filter( 'woocommerce_get_order_item_totals', 'wooposgc_add_order_giftcard', 10, 2);


function wooposgc_giftcard_in_order( $order_id ) {

	$giftCardPayment = get_post_meta( $order_id, 'wooposgc_payment', true);

	if ( $giftCardPayment ) { 
		foreach ($giftCardPayment as $key => $payment ) {?>
	
		<tr>
			<td class="label"><?php _e( 'Gift Card Payment', 'wooposgc' ); ?> <span class="tips" data-tip="<?php _e( 'This is the amount used by gift cards.', 'wooposgc' ); ?>">[?]</span>:</td>
			<td class="total"><?php echo wc_price($payment); ?></td>
			<td width="1%"></td>
		</tr>
	
	<?php  
		}
	}

}
add_action( 'woocommerce_admin_order_totals_after_tax', 'wooposgc_giftcard_in_order', 10, 1 );
