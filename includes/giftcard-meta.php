<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Updates a giftcard's status from one status to another.
 *
 * @since 1.0
 * @param int $code_id Giftcard ID (default: 0)
 * @param string $new_status New status (default: active)
 * @return bool
 */
function wooposgc_update_giftcard_status( $code_id = 0, $new_status = 'active' ) {
	$giftcard = wooposgc_get_giftcard( $code_id );

	if ( $giftcard ) {
		do_action( 'wooposgc_pre_update_giftcard_status', $code_id, $new_status, $giftcard->post_status );

		wp_update_post( array( 'ID' => $code_id, 'post_status' => $new_status ) );

		do_action( 'wooposgc_post_update_giftcard_status', $code_id, $new_status, $giftcard->post_status );

		return true;
	}

	return false;
}

/**
 * Retrieve the giftcard number
 *
 * @since 1.4
 * @param int $code_id Giftcard ID
 * @return string $expiration Giftcard expiration
 */
function wooposgc_get_giftcard_info( $code_id = null ) {
	$giftcard = get_post_meta( $code_id, '_wooposgc_giftcard', true );

	return apply_filters( 'wooposgc_get_giftcard_info', $giftcard, $code_id );
}

/**
 * Retrieve the giftcard number
 *
 * @since 1.4
 * @param int $code_id Giftcard ID
 * @return string $expiration Giftcard expiration
 */
function wooposgc_set_giftcard_info( $code_id, $giftInfo ) {

	update_post_meta( $code_id, '_wooposgc_giftcard', $giftInfo );
}

/**
 * Get Giftcard
 *
 * Retrieves a complete giftcard code by giftcard ID.
 *
 * @since 1.0
 * @param string $giftcard_id Giftcard ID
 * @return array
 */
function wooposgc_get_giftcard( $giftcard_id ) {
	$giftcard = get_post( $giftcard_id );

	if ( get_post_type( $giftcard_id ) != 'wooposgc_giftcard' ) {
		return false;
	}

	return $giftcard;
}

/**
 * Retrieve the giftcard ID from the gift card number
 *
 * @since 1.4
 * @param int $code_id Giftcard ID
 * @return string $expiration Giftcard expiration
 */
function wooposgc_get_giftcard_by_code( $value = '' ) {
	global $wpdb;

	// Check for Giftcard
	$giftcard_found = $wpdb->get_var( $wpdb->prepare( "
		SELECT $wpdb->posts.ID
		FROM $wpdb->posts
		WHERE $wpdb->posts.post_type = 'wooposgc_giftcard'
		AND $wpdb->posts.post_status = 'publish'
		AND $wpdb->posts.post_title = '%s'
	", $value ) );

	return $giftcard_found;

}

/**
 * Retrieve the giftcard number
 *
 * @since 1.4
 * @param int $code_id Giftcard ID
 * @return string $expiration Giftcard expiration
 */
function wooposgc_get_giftcard_number( $code_id = null ) {
	$giftcardNumber = get_the_title( $code_id );

	return apply_filters( 'wooposgc_get_giftcard_number', $giftcardNumber, $code_id );
}

/**
 * Retrieve the giftcard to name
 *
 * @since 1.4
 * @param int $code_id
 * @return string $code Giftcard To Name
 */
function wooposgc_get_giftcard_to( $code_id = null ) {
	$giftcard = wooposgc_get_giftcard_info( $code_id );

	return apply_filters( 'wooposgc_get_giftcard_to', $giftcard['to'], $code_id );
}

/**
 * Retrieve the giftcard to email
 *
 * @since 1.4
 * @param int $code_id
 * @return string $code Giftcard To Email
 */
function wooposgc_get_giftcard_to_email( $code_id = null ) {
	$giftcard = wooposgc_get_giftcard_info( $code_id );

	return apply_filters( 'wooposgc_get_giftcard_toEmail', $giftcard['toEmail'], $code_id );
}

/**
 * Retrieve the giftcard from
 *
 * @since 1.4
 * @param int $code_id
 * @return string $code Giftcard From Name
 */
function wooposgc_get_giftcard_from( $code_id = null ) {
	$giftcard = wooposgc_get_giftcard_info( $code_id );

	return apply_filters( 'wooposgc_get_giftcard_from', $giftcard['from'], $code_id );
}

/**
 * Retrieve the giftcard from email
 *
 * @since 1.4
 * @param int $code_id
 * @return string $code Giftcard From Email
 */
function wooposgc_get_giftcard_from_email( $code_id = null ) {
	$giftcard = wooposgc_get_giftcard_info( $code_id );

	return apply_filters( 'wooposgc_get_giftcard_fromEmail', $giftcard['fromEmail'], $code_id );
}

/**
 * Retrieve the giftcard note
 *
 * @since 1.4
 * @param int $code_id
 * @return string $code Giftcard Note
 */
function wooposgc_get_giftcard_note( $code_id = null ) {
	$giftcard = wooposgc_get_giftcard_info( $code_id );

	return apply_filters( 'wooposgc_get_giftcard_note', $giftcard['note'], $code_id );
}

/**
 * Retrieve the giftcard code expiration date
 *
 * @since 1.4
 * @param int $code_id Giftcard ID
 * @return string $expiration Giftcard expiration
 */
function wooposgc_get_giftcard_expiration( $code_id = null ) {
	$giftcard = wooposgc_get_giftcard_info( $code_id );

	return apply_filters( 'wooposgc_get_giftcard_expiration', $giftcard['expiry_date'], $code_id );
}

/**
 * Retrieve the giftcard amount
 *
 * @since 1.4
 * @param int $code_id Giftcard ID
 * @return int $amount Giftcard code amount
 * @return float
 */
function wooposgc_get_giftcard_amount( $code_id = null ) {
	$giftcard = wooposgc_get_giftcard_info( $code_id );

	return (float) apply_filters( 'wooposgc_get_giftcard_amount', $giftcard['amount'], $code_id );
}

/**
 * Retrieve the giftcard balance
 *
 * @since 1.4
 * @param int $code_id Giftcard ID
 * @return int $amount Giftcard code balance
 * @return float
 */
function wooposgc_get_giftcard_balance( $code_id = null, $raw = false ) {
	$giftcard = wooposgc_get_giftcard_info( $code_id );

	return (float) ( $raw ? $giftcard['balance'] : apply_filters( 'wooposgc_get_giftcard_balance', $giftcard['balance'] ) );
}

/**
 * Set the giftcard balance
 *
 * @since 1.4
 * @param int $code_id Giftcard ID
 * @return int $amount Giftcard code balance
 * @return float
 */
function wooposgc_set_giftcard_balance( $code_id, $balance ) {
	$giftcard = wooposgc_get_giftcard_info( $code_id );
	
	$giftcard['balance'] = (string) $balance;
	
	if ( get_option( 'wooposgc_enable_one_time_use' ) == 'yes' ){
		$giftcard['balance'] = (string) 0;
	}
	

	wooposgc_set_giftcard_info( $code_id, $giftcard );
}



// Order Gift Card Functions
// ******************************************************************************************


function wooposgc_get_order_card_ids ( $order_id = null ) {
	$ids = get_post_meta( $order_id, 'wooposgc_id', true );
	

	return apply_filters( 'wooposgc_get_order_card_ids', $ids, $order_id );
}



function wooposgc_get_order_card_number ( $order_id = null ) {
	$id = get_post_meta( $order_id, 'wooposgc_id', true );
	$number = get_the_title( $id );

	return apply_filters( 'wooposgc_get_order_card_number', $number, $order_id );
}



function wooposgc_get_order_card_numbers ( $order_id = null ) {
	$ids = get_post_meta( $order_id, 'wooposgc_id', true );
	
	$numbers = array();

	foreach ($ids as $key => $id) {
		$numbers[] = get_the_title( $id );
	}

	return apply_filters( 'wooposgc_get_order_card_numbers', $numbers, $order_id );
}

function wooposgc_get_order_card_balance ( $order_id = null ) {
	$balance = get_post_meta( $order_id, 'wooposgc_balance', true );

	return apply_filters( 'wooposgc_get_order_card_balance', $balance, $order_id );
}

function wooposgc_get_order_card_payment ( $order_id = null ) {
	$payment = get_post_meta( $order_id, 'wooposgc_payment', true );

	return apply_filters( 'wooposgc_get_order_card_payment', $payment, $order_id );
}

function wooposgc_get_order_refund_status ( $order_id = null ) {
	$refunded = get_post_meta( $order_id, 'wooposgc_refunded', true );

	return apply_filters( 'wooposgc_get_order_refund_status', $refunded, $order_id );
}

// Get Gift Card messages
// ******************************************************************************************


function wooposgc_get_custom_message ( ) {
	$message = get_option( 'wooposgc_enable_giftcard_custom_message' );

	if ( $message == '' ) {
		return 'default';
	}

	return apply_filters( 'wooposgc_get_custom_message', $message );
}

function wooposgc_get_custom_instructions ( ) {
	$instructions = get_option( 'wooposgc_enable_giftcard_redemption_info' );

	if ( $instructions == '' ) {
		return 'default';
	}

	return apply_filters( 'wooposgc_get_custom_instructions', $instructions );
}