<?php
/**
 * WooCommerce Gift Card Pro Settings
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly



function wooposgc_pro_settings( $sections ){

	$pro = array( 'pro' => __( 'In Store Credit', 'wooposgc' ) );

	return array_merge( $sections, $pro );

}
//add_filter ('woocommerce_add_section_giftcard', 'wooposgc_pro_settings' );


function wooposgc_pro_add_section ( $options, $current_section ){

	if( $current_section == 'pro' ) {

		$options = array(

				array( 'type' => 'sectionend', 'id' => 'wooposgc_cn_settings'),

				array( 'title' 		=> __( 'In Store Credit',  'wooposgc'  ), 'type' => 'title', 'id' => 'giftcard_processing_options_title' ),

				array(
					'name'		=> __( 'Remaining Gift Card Funds', 'wooposgc' ),
					'desc'		=> __( 'How do you want to handle remaining funds on the gift card.', 'wooposgc' ),
					'id'		=> 'wooposgc_handle_isc',
					'std'		=> '', // WooCommerce < 2.0
					'default'	=> '', // WooCommerce >= 2.0
					'type'		=> 'select',
					'class'		=> 'chosen_select',
					'options'	=> array(
						'never'		=> __( 'Never convert to ISC', 'wooposgc' ),
						'always'	=> __( 'Always convert to ISC', 'wooposgc' ),
						'ask'		=> __( 'Ask customer', 'wooposgc' )
						
					),
					'desc_tip' =>  true,
				),

				array( 'type' => 'sectionend', 'id' => 'wooposgc_isc_settings'),
		);
	}

	return $options;

}
//add_filter ('get_giftcard_settings', 'wooposgc_pro_add_section', 10, 2);