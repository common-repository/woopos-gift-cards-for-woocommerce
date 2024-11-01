<?php


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;



function wooposgc_extra_check( $product_type_options ) {

	$giftcard = array(
		'wooposgc_isgiftcard' => array(
			'id' => '_wooposgc_isgiftcard',
			'wrapper_class' => 'show_if_simple show_if_variable',
			'label' => __( 'Gift Card', 'wooposgc' ),
			'description' => __( 'Make product a gift card.', 'wooposgc' )
		),
	);

	// combine the two arrays
	$product_type_options = array_merge( $giftcard, $product_type_options );

	return apply_filters( 'wooposgc_extra_check', $product_type_options );
}
add_filter( 'product_type_options', 'wooposgc_extra_check' );

function wooposgc_process_meta( $post_id, $post ) {
	global $wpdb, $woocommerce, $woocommerce_errors;

	if ( get_post_type( $post_id ) == 'product' ) {

		$is_giftcard  = isset( $_POST['_wooposgc_isgiftcard'] ) ? 'yes' : 'no';

		if( $is_giftcard == 'yes' ) {

			update_post_meta( $post_id, '_wooposgc_isgiftcard', $is_giftcard );

			if ( get_option( "wooposgc_enable_multiples") != "yes" ) {
				update_post_meta( $post_id, '_sold_individually', $is_giftcard );
			}

			$want_physical = get_option( 'wooposgc_enable_physical' );

			if ( $want_physical == "no" ) {
				update_post_meta( $post_id, '_virtual', $is_giftcard );
			}

			$reload = isset( $_POST['_wooposgc_allow_reload'] ) ? 'yes' : 'no';
			$disable_coupons = isset( $_POST['_wooposgc_disable_coupon'] ) ? 'yes' : 'no';
			$physical = isset( $_POST['_wooposgc_physical_card'] ) ? 'yes' : 'no';


			update_post_meta( $post_id, '_wooposgc_allow_reload', $reload );
			update_post_meta( $post_id, '_wooposgc_physical_card', $physical );

			do_action( 'wooposgc_add_other_giftcard_options', $post_id, $post );

		} else {
			delete_post_meta( $post_id, '_wooposgc_isgiftcard' );
			delete_post_meta( $post_id, 'wooposgc_giftcard' );
		}
	}
}
add_action( 'save_post', 'wooposgc_process_meta', 10, 2 );


//  Sets a unique ID for gift cards so that multiple giftcards can be purchased (Might move to the main gift card Plugin)
function wooposgc_uniqueID($cart_item_data, $product_id) {
	$is_giftcard = get_post_meta( $product_id, '_wooposgc_isgiftcard', true );
    if ( $is_giftcard != "yes" ) $is_giftcard = get_post_meta( $product_id, 'wooposgc_giftcard', true );

	if ( $is_giftcard == "yes" ) {

		$unique_cart_item_key = md5("gc" . microtime().rand());
		$cart_item_data['unique_key'] = $unique_cart_item_key;

	}

	return apply_filters( 'wooposgc_uniqueID', $cart_item_data, $product_id );
}
add_filter('woocommerce_add_cart_item_data','wooposgc_uniqueID',10,2);



function wooposgc_change_add_to_cart_button ( $link ) {
	global $post;

	if ( preventAddToCart( $post->ID ) ) {
		$giftCard_button = get_option( "wooposgc_giftcard_button" );

		if( $giftCard_button <> '' ){
			$giftCardText = get_option( "wooposgc_giftcard_button" );
		} else {
			$giftCardText = 'Customize';
		}

		$link = '<a href="' . esc_url( get_permalink( $post->ID ) ) . '" rel="nofollow" data-product_id="' . esc_attr( $post->ID ) . '" data-product_sku="' . esc_attr( $post->ID ) . '" class="button product_type_' . esc_attr( $post->product_type ) . '">' . $giftCardText . '</a>';
	}

	return  apply_filters( 'wooposgc_change_add_to_cart_button', $link, $post);
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'wooposgc_change_add_to_cart_button' );


function preventAddToCart( $id ){
	$return = false;
	$is_giftcard = get_post_meta( $id, '_wooposgc_isgiftcard', true );
    if ( $is_giftcard != "yes" ) $is_giftcard = get_post_meta( $id, 'wooposgc_giftcard', true );

	if ( $is_giftcard == "yes" && get_option( 'wooposgc_enable_addtocart' ) == "yes" )
		$return = true;

	return apply_filters( 'wooposgc_preventAddToCart', $return, $id );
}


function wooposgc_cart_fields( ) {
	global $post;

    $is_giftcard = get_post_meta( $post->ID, '_wooposgc_isgiftcard', true );
    if ( $is_giftcard != "yes" ) $is_giftcard = get_post_meta( $post->ID, 'wooposgc_giftcard', true );

	$is_required_field_giftcard = get_option( 'wooposgc_enable_giftcard_info_requirements' );

	if ( $is_giftcard == 'yes' ) {
		$is_reload		= get_post_meta( $post->ID, '_wooposgc_allow_reload', true );
		$is_physical	= get_post_meta( $post->ID, '_wooposgc_physical_card', true );

		do_action( 'wooposgc_before_all_giftcard_fields', $post );

		$wooposgc_to 			= ( isset( $_POST['wooposgc_to'] ) ? sanitize_text_field( $_POST['wooposgc_to'] ) : "" );
		$wooposgc_to_email 		= ( isset( $_POST['wooposgc_to_email'] ) ? sanitize_text_field( $_POST['wooposgc_to_email'] ) : "" );
		$wooposgc_note			= ( isset( $_POST['wooposgc_note'] ) ? sanitize_text_field( $_POST['wooposgc_note'] ) : ""  );
		$wooposgc_address		= ( isset( $_POST['wooposgc_address'] ) ? sanitize_text_field( $_POST['wooposgc_address'] ) : ""  );
		$wooposgc_reloading		= ( isset( $_POST['wooposgc_reload_check'] ) ? sanitize_text_field( $_POST['wooposgc_reload_check'] ) : ""  );
		$wooposgc_reload_number	= ( isset( $_POST['wooposgc_reload_card'] ) ? sanitize_text_field( $_POST['wooposgc_reload_card'] ) : ""  );

		$wooposgc_to_check 		= ( get_option( 'wooposgc_giftcard_to' ) <> NULL ? get_option( 'wooposgc_giftcard_to' ) : __('To', 'wooposgc' ) );
		$wooposgc_toEmail_check 	= ( get_option( 'wooposgc_giftcard_toEmail' ) <> NULL ? get_option( 'wooposgc_giftcard_toEmail' ) : __('To Email', 'wooposgc' )  );
		$wooposgc_note_check		= ( get_option( 'wooposgc_giftcard_note' ) <> NULL ? get_option( 'wooposgc_giftcard_note' ) : __('Note', 'wooposgc' )  );
		$wooposgc_address_check	= ( get_option( 'wooposgc_giftcard_address' ) <> NULL ? get_option( 'wooposgc_giftcard_address' ) : __('Address', 'wooposgc' )  );
		//$wooposgc_physical_card 	= ( get_option( 'wooposgc_giftcard_to' ) <> NULL ? get_option( 'wooposgc_giftcard_to' ) : __('To', 'wooposgc' ) );
?>

		<div>
			<?php if ( $is_required_field_giftcard == "yes" ) { ?>
				<div class="wooposgc_product_message hide-on-reload"><?php _e('All fields below are required', 'wooposgc' ); ?></div>
			<?php } else { ?>
				<div class="wooposgc_product_message hide-on-reload"><?php _e('All fields below are optional', 'wooposgc' ); ?></div>
			<?php } ?>
			
			<?php  do_action( 'wooposgc_before_product_fields' ); ?>

			<input type="hidden" id="wooposgc_description" name="wooposgc_description" value="<?php _e('Generated from the website.', 'wooposgc' ); ?>" />
			<input type="text" name="wooposgc_to" id="wooposgc_to" class="input-text hide-on-reload" style="margin-bottom:5px;" placeholder="<?php echo $wooposgc_to_check; ?>" value="<?php echo $wooposgc_to; ?>">
			
			<?php if ( $is_physical == 'yes' ) { ?>
				<textarea class="input-text hide-on-reload" id="wooposgc_address" name="wooposgc_address" rows="2" style="margin-bottom:5px;" placeholder="<?php echo $wooposgc_address_check; ?>"><?php echo $wooposgc_address; ?></textarea>
			<?php } else { ?> 
				<input type="email" name="wooposgc_to_email" id="wooposgc_to_email" class="input-text hide-on-reload" placeholder="<?php echo $wooposgc_toEmail_check; ?>" style="margin-bottom:5px;" value="<?php echo $wooposgc_to_email; ?>">
			<?php } ?>
			<?php if ( get_option( 'wooposgc_woocommerce_disable_notes' ) != 'yes' ) { ?>
				<textarea class="input-text hide-on-reload" id="wooposgc_note" name="wooposgc_note" rows="2" style="margin-bottom:5px;" placeholder="<?php echo $wooposgc_note_check; ?>"><?php echo $wooposgc_note; ?></textarea>
			<?php } ?>
			<?php if ( $is_reload == "yes" ) { ?>
				<input type="checkbox" name="wooposgc_reload_check" id="wooposgc_reload_check" <?php if ( $wooposgc_reloading == "on") { echo "checked=checked"; } ?>> <?php _e('Reload existing Gift Card', 'wooposgc' ); ?>
				<input type="text" name="wooposgc_reload_card" id="wooposgc_reload_card" class="input-text show-on-reload" style="margin-bottom:5px; display:none;" placeholder="<?php _e('Enter Gift Card Number', 'wooposgc' ); ?>" value="<?php echo $wooposgc_reload_number; ?>">
			<?php } ?>

			<?php  do_action( 'wooposgc_after_product_fields', $post->ID ); ?>

		</div>
		<?php

		if ( get_option( "wooposgc_enable_multiples") != 'yes' ) {
			echo '
				<script>
					jQuery( document ).ready( function( $ ){ $( ".quantity" ).hide( ); });

					jQuery("#wooposgc_reload_check").change( function( $ ) {
						jQuery(".hide-on-reload").toggle();
					});

				</script>';
		}
	}
}
add_action( 'woocommerce_before_add_to_cart_button', 'wooposgc_cart_fields' );

function wooposgc_add_to_cart_validation( $passed, $product_id, $quantity ) {
    $is_giftcard = get_post_meta( $product_id, '_wooposgc_isgiftcard', true );
    if ( $is_giftcard != "yes" ) $is_giftcard = get_post_meta( $product_id, 'wooposgc_giftcard', true );
	$is_required_field_giftcard = get_option( 'wooposgc_enable_giftcard_info_requirements' );

	if ( isset( $_POST['wooposgc_reload_check'] ) ) {
		if ( ( $_POST['wooposgc_reload_check'] == "on" ) && ( $_POST['wooposgc_reload_card'] != "" ) ) {

			if ( ! wooposgc_get_giftcard_by_code( wc_clean( $_POST['wooposgc_reload_card'] ) ) ) {
				$notice = __( 'Gift card number not Found.', 'wooposgc' );
				wc_add_notice( $notice, 'error' );
				$passed = false;
			}

			$passed = apply_filters( 'wooposgc_other_validations', $passed, $product_id, $quantity );
		}
	}

	if ( $is_required_field_giftcard == "yes" && $is_giftcard == "yes" ) {

		if ( ! isset( $_POST['wooposgc_to_email'] ) || $_POST['wooposgc_to_email'] == "" ) {
			if ( get_post_meta( $product_id, '_wooposgc_physical_card', true ) == "no" ) {
				$notice = __( 'Please enter an email address for the gift card.', 'wooposgc' );
				wc_add_notice( $notice, 'error' );
				$passed = false;
			}
		}

		if ( ! isset( $_POST['wooposgc_to'] ) || $_POST['wooposgc_to'] == "" ) {
			$notice = __( 'Please enter a name for the gift card.', 'wooposgc' );
			wc_add_notice( $notice, 'error' );
			$passed = false;
		}

		if ( ! isset( $_POST['wooposgc_note'] ) || $_POST['wooposgc_note'] == "" ) {
			$notice = __( 'Please enter a note for the gift card.', 'wooposgc' );
			wc_add_notice( $notice, 'error' );
			$passed = false;
		}

		$passed = apply_filters( 'wooposgc_other_validations', $passed, $product_id, $quantity );
	}

	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'wooposgc_add_to_cart_validation', 10, 3 );


function wooposgc_add_card_data( $cart_item_key, $product_id, $quantity ) {
	global $woocommerce, $post;

    $is_giftcard = get_post_meta( $product_id, '_wooposgc_isgiftcard', true );
    if ( $is_giftcard != "yes" ) $is_giftcard = get_post_meta( $product_id, 'wooposgc_giftcard', true );

	if ( $is_giftcard == "yes" ) {

		$wooposgc_to_check 				= ( get_option( 'wooposgc_giftcard_to' ) <> NULL ? get_option( 'wooposgc_giftcard_to' ) : __('To', 'wooposgc' ) );
		$wooposgc_toEmail_check 			= ( get_option( 'wooposgc_giftcard_toEmail' ) <> NULL ? get_option( 'wooposgc_giftcard_toEmail' ) : __('To Email', 'wooposgc' )  );
		$wooposgc_note_check				= ( get_option( 'wooposgc_giftcard_note' ) <> NULL ? get_option( 'wooposgc_giftcard_note' ) : __('Note', 'wooposgc' )  );
		$wooposgc_reload_card			= ( get_option( 'wooposgc_giftcard_reload_card' ) <> NULL ? get_option( 'wooposgc_giftcard_reload_card' ) : __('Card Number', 'wooposgc' )  );
		$wooposgc_address_check			= ( get_option( 'wooposgc_giftcard_address' ) <> NULL ? get_option( 'wooposgc_giftcard_address' ) : __('Address', 'wooposgc' )  );

		$giftcard_data = array(
			$wooposgc_to_check    	=> '',
			$wooposgc_toEmail_check  => '',
			$wooposgc_note_check   	=> '',
			$wooposgc_reload_card	=> '',
			$wooposgc_address_check  => '',

		);

		if ( isset( $_POST['wooposgc_to'] ) && ( $_POST['wooposgc_to'] <> '' ) )
			$giftcard_data[$wooposgc_to_check] = wc_clean( $_POST['wooposgc_to'] );

		if ( isset( $_POST['wooposgc_to_email'] ) && ( $_POST['wooposgc_to_email'] <> '' ) )
			$giftcard_data[$wooposgc_toEmail_check] = wc_clean( $_POST['wooposgc_to_email'] );

		if ( isset( $_POST['wooposgc_note'] ) && ( $_POST['wooposgc_note'] <> '' ) )
			$giftcard_data[$wooposgc_note_check] = wc_clean( $_POST['wooposgc_note'] );

		if ( isset( $_POST['wooposgc_address'] ) && ( $_POST['wooposgc_address'] <> '' ) ) {
			$giftcard_data[$wooposgc_address_check] = wc_clean( $_POST['wooposgc_address'] );
		}

		if ( isset( $_POST['wooposgc_reload_card'] ) && ( $_POST['wooposgc_reload_card'] <> '' ) ) {
			$giftcard_data[$wooposgc_reload_card] = wc_clean( $_POST['wooposgc_reload_card'] );
		}

		$giftcard_data = apply_filters( 'wooposgc_giftcard_data', $giftcard_data, $_POST );

		WC()->cart->cart_contents[$cart_item_key]["variation"] = $giftcard_data;
		return $woocommerce;
	}

}
add_action( 'woocommerce_add_to_cart', 'wooposgc_add_card_data', 10, 6 );

function wooposgc_ajax_add_card_data( $product_id ) {
	global $woocommerce, $post;

    $is_giftcard = get_post_meta( $product_id, '_wooposgc_isgiftcard', true );
    if ( $is_giftcard != "yes" ) $is_giftcard = get_post_meta( $product_id, 'wooposgc_giftcard', true );

	if ( $is_giftcard == "yes" ) {

		$wooposgc_to_check 				= ( get_option( 'wooposgc_giftcard_to' ) <> NULL ? get_option( 'wooposgc_giftcard_to' ) : __('To', 'wooposgc' ) );
		$wooposgc_toEmail_check 			= ( get_option( 'wooposgc_giftcard_toEmail' ) <> NULL ? get_option( 'wooposgc_giftcard_toEmail' ) : __('To Email', 'wooposgc' )  );
		$wooposgc_note_check				= ( get_option( 'wooposgc_giftcard_note' ) <> NULL ? get_option( 'wooposgc_giftcard_note' ) : __('Note', 'wooposgc' )  );
		$wooposgc_reload_card			= ( get_option( 'wooposgc_giftcard_reload_card' ) <> NULL ? get_option( 'wooposgc_giftcard_reload_card' ) : __('Card Number', 'wooposgc' )  );
		$wooposgc_address_check			= ( get_option( 'wooposgc_giftcard_address' ) <> NULL ? get_option( 'wooposgc_giftcard_address' ) : __('Address', 'wooposgc' )  );

		$giftcard_data = array(
			$wooposgc_to_check    	=> '',
			$wooposgc_toEmail_check  => '',
			$wooposgc_note_check   	=> '',
			$wooposgc_reload_card	=> '',
			$wooposgc_address_check  => '',

		);

		if ( isset( $_POST['wooposgc_to'] ) && ( $_POST['wooposgc_to'] <> '' ) )
			$giftcard_data[$wooposgc_to_check] = wc_clean( $_POST['wooposgc_to'] );

		if ( isset( $_POST['wooposgc_to_email'] ) && ( $_POST['wooposgc_to_email'] <> '' ) )
			$giftcard_data[$wooposgc_toEmail_check] = wc_clean( $_POST['wooposgc_to_email'] );

		if ( isset( $_POST['wooposgc_note'] ) && ( $_POST['wooposgc_note'] <> '' ) )
			$giftcard_data[$wooposgc_note_check] = wc_clean( $_POST['wooposgc_note'] );

		if ( isset( $_POST['wooposgc_address'] ) && ( $_POST['wooposgc_address'] <> '' ) ) {
			$giftcard_data[$wooposgc_address_check] = wc_clean( $_POST['wooposgc_address'] );
		}

		if ( isset( $_POST['wooposgc_reload_card'] ) && ( $_POST['wooposgc_reload_card'] <> '' ) ) {
			$giftcard_data[$wooposgc_reload_card] = wc_clean( $_POST['wooposgc_reload_card'] );
		}

		$giftcard_data = apply_filters( 'wooposgc_giftcard_data', $giftcard_data, $_POST );

		WC()->cart->cart_contents[$cart_item_key]["variation"] = $giftcard_data;
		return $woocommerce;
	}

}
add_action( 'woocommerce_ajax_added_to_cart', 'wooposgc_ajax_add_card_data', 10, 1 );




function wooposgc_add_giftcard_data_tab( $product_data_tabs ) {

	$giftcard = array(
				'wooposgc_isgiftcard' => array(
					'label'  => __( 'Gift Card', 'wooposgc' ),
					'target' => 'giftcard_product_data',
					'class'  => array( 'hide_if_not_giftcard' ),
				));

	$product_data_tabs = array_merge($product_data_tabs , $giftcard);

	return $product_data_tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'wooposgc_add_giftcard_data_tab' );


function wooposgc_add_giftcard_panel () {
?>
	<div id="giftcard_product_data" class="panel woocommerce_options_panel hidden">
		<?php

		echo '<div class="options_group">';
			woocommerce_wp_checkbox( array( 'id' => '_wooposgc_allow_reload', 'wrapper_class' => 'show_if_simple show_if_variable', 'label' => __( 'Allow Reload', 'wooposgc' ), 'description' => __( 'Enable this allow people to enter in their gift card number to reload funds.', 'wooposgc' ) ) );
		echo '</div>';

		echo '<div class="options_group">';
			woocommerce_wp_checkbox( array( 'id' => '_wooposgc_physical_card', 'wrapper_class' => 'show_if_simple show_if_variable', 'label' => __( 'Physical Card?', 'wooposgc' ), 'description' => __( 'Enable this if you are sending out physical cards.', 'wooposgc' ) ) );
		echo '</div>';

		do_action( 'woocommerce_product_options_giftcard_data' );
		?>

	</div>
	<?php
}
add_action( 'woocommerce_product_data_panels', 'wooposgc_add_giftcard_panel' );
