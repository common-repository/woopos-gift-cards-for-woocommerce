<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//*************************************//

if( ! class_exists( 'WOOPOSGC_Custom_Price') ) {
	class WOOPOSGC_Custom_Price {
		private static $wooposgc_wg_instance;

		/**
		 * Get the singleton instance of our plugin
		 * @return class The Instance
		 * @access public
		 */
		public static function getInstance() {

		
			if ( !self::$wooposgc_wg_instance  ) {
				self::$wooposgc_wg_instance = new WOOPOSGC_Custom_Price();
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


			add_action( 'get_giftcard_settings', array( $this, 'wooposgc_customprice_page'), 10, 2);
			add_filter( 'woocommerce_add_section_giftcard', array( $this, 'wooposgc_customprice_settings') );

			if ( get_option( 'wooposgc_enable_price_customization' ) != "no" ) {
				add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'wooposgc_check_card_price'), 10, 3 );
				add_filter( 'woocommerce_cart_item_name', array( $this, 'wooposgc_uniqueTitle'), 10, 3);
				add_action( 'woocommerce_before_calculate_totals', array( $this, 'wooposgc_add_custom_price') );
				add_filter( 'wooposgc_giftcard_data', array( $this, 'wooposgc_add_cart_item'), 10, 1);
				add_filter( 'woocommerce_get_price_html', array( $this, 'wooposgc_remove_price'), 10, 2 );
				add_action( 'wooposgc_before_all_giftcard_fields', array( $this, 'wooposgc_add_remove_field'), 10, 1 );
				add_filter( 'wooposgc_preventAddToCart', array( $this, 'wooposgc_cp_prevent'), 10, 2 );
				add_filter( 'woocommerce_cart_item_price', array( $this, 'wooposgc_mini_cart'), 10, 3);
				
				if( is_admin() ) {
					add_action( 'woocommerce_product_options_giftcard_data', array( $this, 'wooposgc_cp_check'), 20, 1 );
					add_action( 'save_post', array( $this, 'wooposgc_add_cp_giftcard_options'), 10, 2 );

			    }
			}

	    }

		public function wooposgc_custom_price_option() {
			$license 	= get_option( 'wooposgc_license_key' );
			$status 	= get_option( 'wooposgc_custom_price_license_status' );

			settings_fields('wooposgc-options');

			if( isset( $_POST["wooposgc_custom_price_license_key"] ) ) {
				$license_key = sanitize_text_field( $_POST["wooposgc_custom_price_license_key"] );

				$license["customPrice"] = $this->wooposgc_sanitize_license( $license_key );

				update_option( 'wooposgc_license_key', $license );
			}

			?>
			<tr valign="top">	
				<th scope="row" valign="middle" style="text-align: left;">
					<?php _e('WooCommerce - Gift Cards | Custom Price'); ?>
				</th>
				<td>
					<input id="wooposgc_custom_price_license_key" name="wooposgc_custom_price_license_key" type="text" class="regular-text" placeholder="<?php _e('Enter License Number', 'wooposgc' ); ?>" value="<?php esc_attr_e( $license["customPrice"] ); ?>" <?php if( false !== $license[ "customPrice" ] && $status !== false && $status == 'valid' ) { ?> style="border: 1px solid green;" <?php } ?> />
					<input id="wooposgc_custom_price_options" name="wooposgc_custom_price_options" type="hidden" value="wooposgc_custom_price_license_status" />
					<?php 
					if( "" !== $license["customPrice"] ) {  
						if( $status !== false && $status == 'valid' ) { 
					?>
							<?php wp_nonce_field( 'wooposgc_custom_price_de_nonce', 'wooposgc_custom_price_de_nonce' ); ?>
							<input type="submit" class="button-secondary" name="wooposgc_custom_price_license_deactivate" value="<?php _e('Deactivate License', 'wooposgc' ); ?>"/>
						<?php } else {
							wp_nonce_field( 'wooposgc_custom_price_ac_nonce', 'wooposgc_custom_price_ac_nonce' ); ?>
							<input type="submit" class="button-secondary" name="wooposgc_custom_price_license_activate" value="<?php _e('Activate License', 'wooposgc' ); ?>"/>
						<?php } ?>
					<?php } ?>

				</td>
			</tr>
			<br />
			<?php
		}


		public function wooposgc_register_settings() {
			// creates our settings in the options table

			register_setting('wooposgc-options', 'wooposgc_options' );
		}

		/**
		 * Sanatize the liscense key being provided
		 * @param  string $new The License key provided
		 * @return string      Sanitized license key
		 */
		public function wooposgc_sanitize_license( $new ) {

			$keys = get_option( 'wooposgc_license_key' );
			$old = $keys["customPrice"];

			if( $old && $old != $new ) {
				delete_option( 'wooposgc_custom_price_license_status' ); // new license has been entered, so must reactivate
			}
			return $new;
		}

	//****************************************************************************************
	//  End License Functions ****************************************************************
	//****************************************************************************************


		//  Adds the box to enter in the cost of the giftcard.
		public function wooposgc_add_remove_field( $post ) {

			$currency_symbol = get_woocommerce_currency_symbol();
			$currency_pos = get_option( 'woocommerce_currency_pos' );

            $is_giftcard = get_post_meta( $post->ID, '_wooposgc_isgiftcard', true );
            if ( $is_giftcard != "yes" ) $is_giftcard = get_post_meta( $post->ID, 'wooposgc_giftcard', true );
 
            $is_custom = get_post_meta( $post->ID, '_wooposgc_cp', true );

			if ( ( $is_giftcard == "yes" ) && ($is_custom == "yes") ) {

				_e('Enter Gift Card Value', 'wooposgc' );
?>

				<br />

				<?php
				$price = '';

				if ( isset( $_POST["wooposgc_price"] ) ) {
					$price = sanitize_text_field( $_POST["wooposgc_price"] );
				}

				switch ( $currency_pos ) {
					case 'left' :
						echo '<strong>' . $currency_symbol . '</strong> <input name="wooposgc_price" id="wooposgc_price" placeholder="' . __('0.00', 'wooposgc' ) . '" class="input-text" style="margin-bottom:5px; width: 150px;" type="text" value="' . $price . '">';
					break;
					case 'right' :
						echo '<input name="wooposgc_price" id="wooposgc_price" placeholder="' . __('0.00', 'wooposgc' ) . '" class="input-text" style="margin-bottom:5px; width: 150px;" type="text" value="' . $price . '"><strong> ' . $currency_symbol . '</strong>';
					break;
					case 'left_space' :
						echo '<strong>' . $currency_symbol . ' </strong> <input name="wooposgc_price" id="wooposgc_price" placeholder="' . __('0.00', 'wooposgc' ) . '" class="input-text" style="margin-bottom:5px; width: 150px;" type="text" value="' . $price . '">';
					break;
					case 'right_space' :
						echo '<input name="wooposgc_price" id="wooposgc_price" placeholder="' . __('0.00', 'wooposgc' ) . '" class="input-text" style="margin-bottom:5px; width: 150px;" type="text" value="' . $price . '"> <strong> ' . $currency_symbol . '</strong>';
					break;
				}
			}
		}

	    public function wooposgc_customprice_settings( $sections ){
	        $price = array( 'price' => __( 'Gift Card Custom Price', 'wooposgc' ) );
	        return array_merge( $sections, $price );

	    }





		function wooposgc_cp_check( $product_type_options ) {

			echo '<div class="options_group">';
				woocommerce_wp_checkbox( array( 'id' => '_wooposgc_cp', 'wrapper_class' => 'show_if_simple show_if_variable', 'label' => __( 'Custom Price?', 'wooposgc' ), 'description' => __( 'Enable this if you are wanting the customer choose the card price.', 'wooposgc' ) ) );
			echo '</div>';


		}


		public function wooposgc_add_cp_giftcard_options( $post_id, $post) {
			global $wpdb, $woocommerce, $woocommerce_errors;

			if ( get_post_type( $post_id ) == 'product' ) {
				$customPrice = isset( $_POST['_wooposgc_cp'] ) ? 'yes' : 'no';
				update_post_meta( $post_id, '_wooposgc_cp', $customPrice );

				$regular_price = get_post_meta( $post_id, '_regular_price', true );
				if( ! isset ($regular_price ) )
					update_post_meta( $post_id, '_regular_price', 0.01 );
			}
		}

		public function wooposgc_cp_process_meta( $post_id, $post ) {
			global $wpdb, $woocommerce, $woocommerce_errors;

			if ( get_post_type( $post_id ) == 'product' ) {
				$is_custom  = isset( $_POST['_wooposgc_cp'] ) ? 'yes' : 'no';
				$regular_price = get_post_meta( $post_id, '_regular_price', true );

				update_post_meta( $post_id, '_wooposgc_cp', $is_custom );

				if( ! isset ($regular_price ) )
					update_post_meta( $post_id, '_regular_price', 0.01 );

			}

		}

		// Removes the display of the price on a gift card product
		public function wooposgc_remove_price( $price, $post ) {

            $is_giftcard = get_post_meta( $post->get_id(), '_wooposgc_isgiftcard', true );
            if ( $is_giftcard != "yes" ) $is_giftcard = get_post_meta( $post->get_id(), 'wooposgc_giftcard', true );
    		$is_custom = get_post_meta( $post->get_id(), '_wooposgc_cp', true );

			if ( ( $is_giftcard == "yes" ) && ($is_custom == "yes") )
				$price = "";

			return $price;
		}

		//  Saves the Gift card amount on adding it to the cart
		public function wooposgc_add_cart_item($data) {

			if ( isset( $_POST['wooposgc_price'] ) )
				$data['price'] = (double) wc_clean( $_POST['wooposgc_price'] );

			return $data;
		}

		//  Replaces the $0 price of the Gift card with the amount entered by the customer
		public function wooposgc_add_custom_price( $cart_object ) {

			foreach ( $cart_object->cart_contents as $key => $value ) {

				if( isset( $value["variation"]["price"] ) ) {
					$value['data']->set_price( $value["variation"]["price"] );
				}

			}
		}

		// Adds the name of the person that the giftcard is being sent to
		public function wooposgc_uniqueTitle( $title, $cart_item, $cart_item_key ) {

            $is_giftcard = get_post_meta( $cart_item [ "product_id" ], '_wooposgc_isgiftcard', true );
            if ( $is_giftcard != "yes" ) $is_giftcard = get_post_meta( $cart_item [ "product_id" ], 'wooposgc_giftcard', true );

			$wooposgc_to_check 		= ( get_option( 'wooposgc_giftcard_to' ) <> NULL ? get_option( 'wooposgc_giftcard_to' ) : __('To', 'wooposgc' ) );
			$wooposgc_toEmail_check 	= ( get_option( 'wooposgc_giftcard_toEmail' ) <> NULL ? get_option( 'wooposgc_giftcard_toEmail' ) : __('To Email', 'wooposgc' )  );
			$wooposgc_note_check		= ( get_option( 'wooposgc_giftcard_note' ) <> NULL ? get_option( 'wooposgc_giftcard_note' ) : __('Note', 'wooposgc' )  );


			if ( $is_giftcard == "yes" ) {
				if ( isset( $cart_item ["variation"][ $wooposgc_to_check ] ) ) {
					$title = $title . ': ' . $cart_item ["variation"][ $wooposgc_to_check ];
				}
			}

			return $title;
		}



		public function wooposgc_customprice_page( $options, $current_section ){

	        if( $current_section == 'price' ) {
				$title = array(
					array( 'title' 		=> __( 'Custom Gift Card Price',  'wooposgc'  ), 'type' => 'title', 'id' => 'giftcard_processing_options_title' )
				);

				$options = apply_filters( 'wooposgc_giftcard_price_settings', array(
					array(
						'name'     => __( 'Minimum Price', 'wooposgc' ),
						'desc'     => __( 'This is the smallest value gift card that you will allow.', 'wooposgc' ),
						'id'       => 'wooposgc_auto_min',
						'std'      => '', // WooCommerce < 2.0
						'default'  => '', // WooCommerce >= 2.0
						'type'     => 'number',
						'desc_tip' =>  true,
					),

					array(
						'name'     => __( 'Maximum Price', 'wooposgc' ),
						'desc'     => __( 'This is the smallest value gift card that you will allow. (No max value? Leave field blank)', 'wooposgc' ),
						'id'       => 'wooposgc_auto_max',
						'std'      => '', // WooCommerce < 2.0
						'default'  => '', // WooCommerce >= 2.0
						'type'     => 'number',
						'desc_tip' =>  true,
					),


					array( 'type' => 'sectionend', 'id' => 'wooposgc_cp_settings'),
				)); // End pages settings

				$options = array_merge($title, $options);
			}
			return $options;

		}

		function wooposgc_fall_back_cart_button ( $link ) {
			global $post;

            $is_giftcard = get_post_meta( $post->ID, '_wooposgc_isgiftcard', true );
            if ( $is_giftcard != "yes" ) $is_giftcard = get_post_meta( $post->ID, 'wooposgc_giftcard', true );

            $is_custom = get_post_meta( $post->ID, '_wooposgc_cp', true );

			if ( $is_giftcard == "yes" && get_option( 'wooposgc_enable_addtocart' ) == "no" && $is_custom == "yes" ) {
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

		function wooposgc_check_card_price ( $passed, $product_id, $quantity ) {
			global $woocommerce;

			$wooposgc_minPrice = get_option( "wooposgc_auto_min" );
			$wooposgc_maxPrice = get_option( "wooposgc_auto_max" );

            $is_giftcard = get_post_meta( $product_id, '_wooposgc_isgiftcard', true );
            if ( $is_giftcard != "yes" ) $is_giftcard = get_post_meta( $product_id, 'wooposgc_giftcard', true );
    		$is_custom = get_post_meta( $product_id, '_wooposgc_cp', true );

			if ( ( $is_giftcard == "yes" ) && ($is_custom == "yes") ){

				if ( ( $_POST["wooposgc_price"] == 0 ) || ( $_POST["wooposgc_price"] == '' ) ) {
					$notice = __( 'Please enter a price for the gift card.', 'wooposgc' );
					wc_add_notice( $notice, 'error' );
					$passed = false;
				}

				//if ( ( $wooposgc_maxPrice == "" ) && ( $wooposgc_minPrice == "" ) ) {
				//	return true;
				//}

				if ($wooposgc_maxPrice == "" ) { $wooposgc_maxPrice = 1000000000000; }
				if ($wooposgc_minPrice == "" ) { $wooposgc_minPrice = 0; }

				if ( $_POST["wooposgc_price"] < $wooposgc_minPrice ) {
					$notice = sprintf( __( 'Please enter a price over %s.', 'wooposgc' ), wc_price( $wooposgc_minPrice ) );
					wc_add_notice( $notice, 'error' );
					$passed = false;
				}

				if ( $_POST["wooposgc_price"] > $wooposgc_maxPrice ) {
					$notice = sprintf( __( 'Please enter a price under %s.', 'wooposgc' ), wc_price( $wooposgc_maxPrice ) );
					wc_add_notice( $notice, 'error' );
					$passed = false;
				}



			}

			return $passed;

		}

		public function wooposgc_mini_cart ( $price, $cart_item, $cart_item_key ){
            $is_giftcard = get_post_meta( $cart_item['product_id'], '_wooposgc_isgiftcard', true );
            if ( $is_giftcard != "yes" ) $is_giftcard = get_post_meta( $cart_item['product_id'], 'wooposgc_giftcard', true );
        	$is_custom = get_post_meta( $cart_item['product_id'], '_wooposgc_cp', true );

			if ( ( $is_giftcard == "yes" ) && ($is_custom == "yes") ){
				$price = wc_price( $cart_item['line_subtotal'] );
			}

			return $price;
		}

		//$product_price = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );

		function wooposgc_cp_prevent( $return, $id ) {

			$is_custom = get_post_meta( $id, '_wooposgc_cp', true );

			if( $is_custom == "yes" )
				$return = true;

			return $return;
		}

	}

}

//*************************************//

