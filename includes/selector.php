<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function wooposgc_price_option( $options ){
	$new_option = array(
		array(
			'name'         => __( 'Enable Price Customization',  'wooposgc_pro'  ),
			'desc'          => __( 'Select this to enable the price customization feature.',  'wooposgc_pro'  ),
			'id'            => 'wooposgc_enable_price_customization',
			'default'       => 'yes',
			'type'          => 'checkbox',
			'desc_tip'		=> true,
		),

	); // End pages settings

	$options = array_merge($new_option, $options);

	return $options;
}
add_filter( 'wooposgc_giftcard_price_settings', 'wooposgc_price_option', 10, 1);

function wooposgc_auto_option( $options ){
	$new_option = array(
		array(
			'name'         => __( 'Enable Auto Send',  'wooposgc_pro'  ),
			'desc'          => __( 'Select this to enable the auto send feature.',  'wooposgc_pro'  ),
			'id'            => 'wooposgc_enable_auto_send',
			'default'       => 'yes',
			'type'          => 'checkbox',
			'desc_tip'		=> true,
		),

	); // End pages settings

	$options = array_merge($new_option, $options);

	return $options;
}
add_filter( 'wooposgc_giftcard_auto_settings', 'wooposgc_auto_option', 10, 1);

function wooposgc_number_option( $options ){
	$new_option = array(
		array(
			'name'         => __( 'Enable Card Number Customization',  'wooposgc_pro'  ),
			'desc'          => __( 'Select this to enable the number customization feature.',  'wooposgc_pro'  ),
			'id'            => 'wooposgc_enable_number_customization',
			'default'       => 'yes',
			'type'          => 'checkbox',
			'desc_tip'		=> true,
		),

	); // End pages settings

	$options = array_merge($new_option, $options);

	return $options;
}
add_filter( 'wooposgc_giftcard_number_settings', 'wooposgc_number_option', 10, 1);
