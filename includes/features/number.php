<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//******************************************//

if( ! class_exists( 'WOOPOSGC_Custom_Number' ) ) {
	
	class WOOPOSGC_Custom_Number {
		private static $wooposgc_wg_instance;

		/**
		 * Get the singleton instance of our plugin
		 * @return class The Instance
		 * @access public
		 */
		public static function getInstance() {

		
			if ( !self::$wooposgc_wg_instance  ) {
				self::$wooposgc_wg_instance = new WOOPOSGC_Custom_Number();
	            self::$wooposgc_wg_instance->hooks();
			}

			return self::$wooposgc_wg_instance;


		}

	    /**
	     * Run action and filter hooks
	     *
	     * @access      private
	     * @since       1.0.0
	     * @return      void
	     *
	     */
	    private function hooks() {
	        
		
			if ( is_admin() ) {
				add_action( 'get_giftcard_settings', array( $this, 'wooposgc_custom_number_settings'), 10, 2);
		    	add_filter( 'woocommerce_add_section_giftcard', array( $this, 'wooposgc_customnumber_page'), 10, 2 );
			}

			add_filter( 'wooposgc_generate_number', array( $this, 'wooposgc_theNumber' ) );
			add_filter( 'wooposgc_regen_number', array( $this, 'wooposgc_theNumber' ) );


	    }

	    public function wooposgc_customnumber_page( $sections ){
	        $number = array( 'number' => __( 'Gift Card Customize Number', 'wooposgc' ) );
	        return array_merge( $sections, $number );

	    }

	    public function wooposgc_custom_number_settings( $options, $current_section ){

	        if( $current_section == 'number' ) {

				$title =  array(
					array( 'title' 		=> __( 'Custom Gift Card Number',  'wooposgc'  ), 'type' => 'title', 'id' => 'giftcard_processing_options_title' ),
				);

				$options = apply_filters( 'wooposgc_giftcard_number_settings', array(
					array(
						'name'     => __( 'Before Number', 'wooposgc' ),
						'desc'     => __( 'This is the value that will display before a gift card number.', 'wooposgc' ),
						'id'       => 'wooposgc_cn_preNum',
						'std'      => '', // WooCommerce < 2.0
						'default'  => '', // WooCommerce >= 2.0
						'type'     => 'text',
						'desc_tip' =>  true,
					),

					array(
						'name'     => __( 'After Number', 'wooposgc' ),
						'desc'     => __( 'This is the value that will display after a gift card number.', 'wooposgc' ),
						'id'       => 'wooposgc_cn_postNum',
						'std'      => '', // WooCommerce < 2.0
						'default'  => '', // WooCommerce >= 2.0
						'type'     => 'text',
						'desc_tip' =>  true,
					),

					array(
						'name'     => __( 'Number of Random Digits', 'wooposgc' ),
						'desc'     => __( 'This will allow you to choose the length of the gift card number. This value does not include the values above.', 'wooposgc' ),
						'id'       => 'wooposgc_cn_numLength',
						'std'      => '15', // WooCommerce < 2.0
						'default'  => '15', // WooCommerce >= 2.0
						'type'     => 'number',
						'desc_tip' =>  true,
					),


					array( 'type' => 'sectionend', 'id' => 'wooposgc_cn_settings'),
				)); // End pages settings

				$options = array_merge( $title, $options );

			}

			return $options;
		}			
			
		public function wooposgc_theNumber ( $data ) {
			// Gets the options from the Database
			$preNumber = get_option( 'wooposgc_cn_preNum' );
			$postNumber = get_option( 'wooposgc_cn_postNum' );
			$length = get_option( 'wooposgc_cn_numLength' );

			
			do {

				if ( $length != false ) {
					$length = isset($length ) ? $length : 15;
				} else {
					$length = 15;
				}

				// Ensures that the length is an integer
				if ( is_string ( $length ) ) 
					$length = (int) $length; 

				$randomNumber = substr( number_format( time() * rand(), 0, '', '' ), 0, $length ); // Generates the random number
						
				$pre = '';
				if( isset( $preNumber )  ) {
					if( $preNumber != '' ) {
						$pre = $preNumber;
					}
				}

				$post = '';
				if( isset( $postNumber )  ) {
					if( $postNumber != '' ) {
						$post = $postNumber;
					}
				}

				$giftCardNumber = '' . (string) $pre . (string) $randomNumber . (string) $post;

			} while ( wooposgc_get_giftcard_by_code( $giftCardNumber ) != 0 );

			return apply_filters( 'wooposgc_customize_number', $giftCardNumber );
		}

		

	}

//******************************************//	
}
