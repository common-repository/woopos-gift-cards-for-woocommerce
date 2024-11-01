<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//******************************************//

if( ! class_exists( 'WOOPOSGC_Auto_Send' ) ) {

	class WOOPOSGC_Auto_Send {
		private static $wooposgc_wg_autosend_instance;

		/**
		 * Get the singleton instance of our plugin
		 * @return class The Instance
		 * @access public
		 */
		public static function getInstance() {

			if ( !self::$wooposgc_wg_autosend_instance  ) {
				self::$wooposgc_wg_autosend_instance = new WOOPOSGC_Auto_Send();
	            self::$wooposgc_wg_autosend_instance->includes();
	            self::$wooposgc_wg_autosend_instance->hooks();
			}
			return self::$wooposgc_wg_autosend_instance;
		}

		/**
         * Include necessary files
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function includes() {

            // Include scripts
        	if( ! class_exists( 'WOOPOSGC_Giftcard' ) ) {
	            require_once WOOPOSGC_DIR . 'includes/class.giftcard.php';
	        }

	        if( ! class_exists( 'WOOPOSGC_Giftcard_Email' ) ) {
		        require_once WOOPOSGC_DIR . 'includes/class.giftcardemail.php';
		    }

        }

		public function wooposgc_auto_send_settings( $options, $current_section ) {

			if( $current_section == 'auto' ) {

				$title = array(
					array( 'title' 		=> __( 'Auto Send Settings',  'wooposgc'  ), 'type' => 'title', 'id' => 'giftcard_processing_options_title' ),
				);

				$options = apply_filters( 'wooposgc_giftcard_auto_settings', array(
					array(
						'name'		=> __( 'Time to Expire', 'wooposgc' ),
						'desc'		=> __( 'Select the number of days you would like cards to be valid for.', 'wooposgc' ),
						'id'		=> 'wooposgc_auto_defaultExpiry',
						'std'		=> '0', // WooCommerce < 2.0
						'default'	=> '0', // WooCommerce >= 2.0
						'type'		=> 'text',
						'desc_tip'	=>  true,
					),

					array(
						'name'		=> __( 'Send', 'wooposgc' ),
						'desc'		=> __( 'Select when you would like the card sent. Example: If you want the card sent imediately, select "On Placing Order"', 'wooposgc' ),
						'id'		=> 'wooposgc_auto_when',
						'std'		=> 'payment', // WooCommerce < 2.0
						'default'	=> 'payment', // WooCommerce >= 2.0
						'type'		=> 'select',
						'class'		=> 'chosen_select',
						'options'	=> array(
							'payment'	=> __( 'On Placing Order', 'wooposgc' ),
							'order'		=> __( 'On Order Completion', 'wooposgc' )
						),
						'desc_tip' =>  true,
					),
					array(
						'name'         => __( 'Enable Send Later',  'wooposgc'  ),
						'desc'          => __( 'Select this to enable the option to send the card later.',  'wooposgc'  ),
						'id'            => 'wooposgc_enable_send_later',
						'default'       => 'no',
						'type'          => 'checkbox',
						'desc_tip'		=> true,
					),

					array(
					'name'     => __( 'Send Later Test', 'wooposgc' ),
					'desc'     => __( 'This is what will display on the send later box.', 'wooposgc' ),
					'id'       => 'woocommerce_giftcard_send_later',
					'std'      => 'Send Later', // WooCommerce < 2.0
					'default'  => 'Send Later', // WooCommerce >= 2.0
					'type'     => 'text',
					'desc_tip' =>  true,
				),



					array( 'type' => 'sectionend', 'id' => 'wooposgc_auto_settings'),
				)); // End pages settings

				$options = array_merge( $title, $options );
			}

			return $options;
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
			if( is_admin() ) {
				add_action( 'get_giftcard_settings', array( $this, 'wooposgc_autosend_page'), 10, 2 );
		    	add_filter( 'woocommerce_add_section_giftcard', array( $this, 'wooposgc_autosend_settings' ) );
		    	add_filter( 'get_giftcard_settings', array( $this, 'wooposgc_auto_send_settings' ), 10, 2 );
		    	add_action( 'wooposgc_woocommerce_options_after_personalize', array( $this, 'wooposgc_add_send_later_field' ), 10, 1 );
			}

			if ( get_option( 'wooposgc_auto_when' ) == NULL ) {
				$wooposgc_when = "order";
			} else {
				$wooposgc_when = get_option( 'wooposgc_auto_when' );
			}

			if( $wooposgc_when == "order" ) {
				add_action( 'woocommerce_order_status_completed', array( $this, 'wooposgc_create_automatically'), 10, 1);
				add_action( 'woocommerce_order_status_completed', array( $this, 'wooposgc_send_automatically'), 20, 1);
			} else {
				add_action( 'woocommerce_order_status_processing', array( $this, 'wooposgc_create_automatically'), 10, 1);
				add_action( 'woocommerce_order_status_processing', array( $this, 'wooposgc_send_automatically'), 20, 1);
			}

			add_action( 'wooposgc_after_product_fields', array( $this, 'wooposgc_setup_send_later'));
			add_filter( 'wooposgc_giftcard_data', array( $this, 'wooposgc_add_gift_card_info'));
			add_action( 'wp_loaded', array( $this, 'wooposgc_send_gift_cards' ) );

	    }


	    public function wooposgc_autosend_page( $options, $current_section ){

	        if( $current_section == 'auto' ) {
	            $options = array(

	                array( 'title'  => __( 'Gift Cards Auto Send',  'wooposgc'  ), 'type' => 'title', 'id' => 'giftcard_autosend_options_title' ),
	                array( 'type'   => 'sectionend', 'id' => 'giftcard_import' ),
	            );
	        }

	        return $options;
	    }

	    public function wooposgc_autosend_settings( $sections ){
	        $auto = array( 'auto' => __( 'Gift Cards Auto Send', 'wooposgc' ) );
	        return array_merge( $sections, $auto );

	    }

	    function wooposgc_add_gift_card_info ( $giftcard_data ) {
			$wooposgc_send_later_card 	= ( get_option( 'woocommerce_giftcard_send_later' ) <> NULL ? get_option( 'woocommerce_giftcard_send_later' ) : __('Send Later', 'wooposgc' )  );

			$gift_send = array(
				$wooposgc_send_later_card => 'NA'
				);

			if ( isset( $_POST[ 'wooposgc_send_later_date' ] ) && ( $_POST[ 'wooposgc_send_later_date' ] <> '' ) ) {
				$gift_send[ $wooposgc_send_later_card ] = wc_clean( $_POST[ 'wooposgc_send_later_date' ] );
				$giftcard_data = array_merge( $giftcard_data, $gift_send);
			}

			return $giftcard_data;
	    }

		public function wooposgc_send_automatically ( $order_id ) {
			$cards = update_post_meta( $order_id, 'wooposgc_numbers', true );

		}

		public function wooposgc_create_automatically ( $order_id ) {
			global $wpdb, $current_user;
			$current_user = wp_get_current_user();

			$order = new WC_Order( $order_id );
			$theItems = $order->get_items();
			$firstName = $order->get_billing_first_name();
			$lastName = $order->get_billing_last_name();

			$wooposgc_to_check 				= ( get_option( 'wooposgc_giftcard_to' ) <> NULL ? get_option( 'wooposgc_giftcard_to' ) : __('To', 'wooposgc' ) );
			$wooposgc_toEmail_check 			= ( get_option( 'wooposgc_giftcard_toEmail' ) <> NULL ? get_option( 'wooposgc_giftcard_toEmail' ) : __('To Email', 'wooposgc' )  );
			$wooposgc_note_check				= ( get_option( 'wooposgc_giftcard_note' ) <> NULL ? get_option( 'wooposgc_giftcard_note' ) : __('Note', 'wooposgc' )  );
			$wooposgc_address_check			= ( get_option( 'wooposgc_giftcard_address' ) <> NULL ? get_option( 'wooposgc_giftcard_address' ) : __('Note', 'wooposgc' )  );
			$wooposgc_send_later				= ( get_option( 'woocommerce_giftcard_send_later' ) <> NULL ? get_option( 'woocommerce_giftcard_send_later' ) : __('Send Later', 'wooposgc' )  );
			$wooposgc_enable_send_later		= ( get_option( 'wooposgc_enable_send_later' ) <> NULL ? get_option( 'wooposgc_enable_send_later' ) : "no"  );
			$wooposgc_reload_card_check 		= ( get_option( 'wooposgc_giftcard_reload_card' ) <> NULL ? get_option( 'wooposgc_giftcard_reload_card' ) : __('Reload Card', 'wooposgc' )  );

			$numberofGiftCards = 0;

			foreach( $theItems as $item ){

				$qty = (int) $item["quantity"];

				$theItem = (int) $item["product_id"];

				$is_giftcard = get_post_meta( $theItem, '_wooposgc_isgiftcard', true );
				if ( $is_giftcard != "yes" ) $is_giftcard = get_post_meta( $theItem, 'wooposgc_giftcard', true );

				$is_physical  = get_post_meta( $theItem, '_wooposgc_physical_card', true );

				if ( $is_giftcard == "yes" ) {

					for ($i = 0; $i < $qty; $i++){

						if ( ( $item["item_meta"][$wooposgc_toEmail_check] == "NA") || ( $item["item_meta"][$wooposgc_reload_card_check] == "" ) ) {

							if( ( $item["item_meta"][$wooposgc_toEmail_check] <> "NA") && ( $item["item_meta"][$wooposgc_toEmail_check] <> "") ) {
								$giftCardInfo[$numberofGiftCards]["To Email"] = $item["item_meta"][$wooposgc_toEmail_check];
							} else {
								$giftCardInfo[$numberofGiftCards]["To Email"] = $current_user->user_email;
							}

							if ( ( $item["item_meta"][$wooposgc_to_check] <> "NA") && ( $item["item_meta"][$wooposgc_to_check] <> "") ) {
								$giftCardInfo[$numberofGiftCards]["To"] = $item["item_meta"][$wooposgc_to_check];
							} else {
								$giftCardInfo[$numberofGiftCards]["To"] = '' . $firstName . ' ' . $lastName . '';
							}

							if ( ( $item["item_meta"][$wooposgc_note_check] <> "NA") && ( $item["item_meta"][$wooposgc_to_check] <> "") ) {
								$giftCardInfo[$numberofGiftCards]["Note"] = $item["item_meta"][$wooposgc_note_check];
							} else {
								$giftCardInfo[$numberofGiftCards]["Note"] = "";
							}

							if ( ( $item["item_meta"][$wooposgc_address_check] <> "NA") && ( $item["item_meta"][$wooposgc_address_check] <> "") ) {
								$giftCardInfo[$numberofGiftCards]["Address"] = $item["item_meta"][$wooposgc_address_check];
							} else {
								$giftCardInfo[$numberofGiftCards]["Address"] = "";
							}

							if ( isset( $item["item_meta"][$wooposgc_send_later] ) ) {
								if ( ( $item["item_meta"][$wooposgc_send_later] <> "NA") && ( $item["item_meta"][$wooposgc_send_later] <> "" ) ) {
									$giftCardInfo[$numberofGiftCards]["SendLater"] = $item["item_meta"][$wooposgc_send_later];
								} else {
									$giftCardInfo[$numberofGiftCards]["SendLater"] = "";
								}
							}

							$product = new WC_Product ( $theItem );

							$giftCardTotal = (float) $item["subtotal"];

							$giftCardInfo[$numberofGiftCards]["Balance"] = $giftCardTotal / $qty;
							$giftCardInfo[$numberofGiftCards]["Amount"] = $giftCardTotal / $qty;
							$giftCardInfo[$numberofGiftCards]["Description"] = 'Generated from Website';
							$giftCardInfo[$numberofGiftCards]["From"] = '' . $firstName . ' ' . $lastName . '';
							$giftCardInfo[$numberofGiftCards]["From Email"] = $order->get_billing_email();
							$giftCardInfo[$numberofGiftCards]["Expiry Date"] = '';
							$giftCardInfo[$numberofGiftCards]["Physical"] = $is_physical;

							$deafultExpiry = ( get_option ( 'wooposgc_auto_defaultExpiry' ) <> NULL ? get_option ( 'wooposgc_auto_defaultExpiry' ) : "NA" );

							if( $deafultExpiry != "NA" ) {
								$delayStart = 0; // Send later days so it gives the right number of days from when it is sent out.
								$timeToExpire = (int) $deafultExpiry;

								$totalTime = $timeToExpire + $delayStart;

								if( $timeToExpire > 0 ) {
									$newdate = strtotime ( '+' . $totalTime . ' day' , strtotime ( 'today' ) ) ;
									$giftCardInfo[$numberofGiftCards]["Expiry Date"] = date ( 'Y-m-j' , $newdate );
								}
							}

							$numberofGiftCards++;
						}
					}

				}

			}

			$giftNumbers = array();

			$giftcard = new WOOPOSGC_Giftcard();
			for ($i = 0; $i < $numberofGiftCards; $i++){

				// Create gift card object
				$my_giftCard = array(
					'post_title'	=> $giftcard->generateNumber(),
					'post_status'	=> 'publish',
					'post_type'		=> 'wooposgc_giftcard',
					'post_author'	=> 1,
				);

				// Insert the gift card into the database
				$post_id = wp_insert_post( $my_giftCard );

				$giftCardInfo[$i]["ID"] = $post_id;
				$giftCardInfo[$i]["Number"] = $my_giftCard["post_title"];

				$giftNumbers[] = $giftCardInfo[$i]["ID"];

				if ( isset( $giftCardInfo[$i]['Description'] ) ) {
		            $giftCard['description']    = wc_clean( $giftCardInfo[$i]['Description'] );
		        }


		        if ( isset( $giftCardInfo[$i]['To'] ) ) {
		            $giftCard['to'] = wc_clean( $giftCardInfo[$i]['To'] );
		        }

		        if ( isset( $giftCardInfo[$i]['To Email'] ) ) {
		        	if ( $giftCardInfo[$i]["To Email"] != "NA") {
						$giftCard['toEmail']        = wc_clean( $giftCardInfo[$i]["To Email"] );
					} else {
						$giftCard['toEmail']        = wc_clean( $giftCardInfo[$i]["From Email"] );
					}
		        }

		        if ( isset( $giftCardInfo[$i]['From'] ) ) {
		            $giftCard['from']           = wc_clean( $giftCardInfo[$i]['From'] );
		        }

		        if ( isset( $giftCardInfo[$i]['From Email'] ) ) {
		            $giftCard['fromEmail']      = wc_clean( $giftCardInfo[$i]['From Email'] );
		        }

		        if ( isset( $giftCardInfo[$i]['Amount'] ) ) {
		            $giftCard['amount']         = wc_clean( $giftCardInfo[$i]['Amount'] );
		            $giftCard['balance']		= $giftCard['amount'];
		        }

		        if ( isset( $giftCardInfo[$i]['Note'] ) ) {
		            $giftCard['note']   = wc_clean( $giftCardInfo[$i]['Note'] );
		        }

		        if ( $giftCardInfo[$i]['Expiry Date'] != '' ) {
		            $giftCard['expiry_date'] = wc_clean( strftime("%Y-%m-%d", strtotime( $giftCardInfo[$i]['Expiry Date'] ) ) );
		        } else {
		            $giftCard['expiry_date'] = '';
		        }

				if ( isset( $giftCardInfo[$i]['Address'] ) ) {
		            $giftCard['Address']      = wc_clean( $giftCardInfo[$i]['Address'] );
		        }

				if ( isset( $giftCardInfo[$i]['SendLater'] ) ) {
		            $giftCard['SendLater']      = wc_clean( $giftCardInfo[$i]['SendLater'] );
		        }

		        if ( isset( $giftCardInfo[$i]['Physical'] ) ) {
		            $physicalCard      = wc_clean( $giftCardInfo[$i]['Physical'] );
		        }

		        $giftCard['sendTheEmail'] = 0;

				update_post_meta( $post_id, '_wooposgc_giftcard', $giftCard );

				$wooposgc_when = ( get_option ( 'wooposgc_auto_when' ) <> NULL ? get_option( 'wooposgc_auto_when' ) : "order" );

				//if ( $wooposgc_when != "order" ) {

					//if ( isset( $giftCard['SendLater'] ) != true ) {
						$email = new WOOPOSGC_Giftcard_Email();
						$post = get_post( $post_id );
						$email->sendEmail ( $post );

						$giftCard['sendTheEmail'] = 1;
						update_post_meta( $post_id, '_wooposgc_giftcard', $giftCard );
					//}
				//}
			}

			update_post_meta( $order_id, 'wooposgc_numbers', $giftNumbers );

		}


		public function wooposgc_setup_send_later( $post_id ) {
			$canSendLater = get_option( 'wooposgc_enable_send_later' );
			$is_reload	  = get_post_meta( $post_id, '_wooposgc_allow_reload', true );
			$is_physical  = get_post_meta( $post_id, '_wooposgc_physical_card', true );

			if ( ( $canSendLater == "yes" ) && ( $is_physical == "no" ) ){
				$wooposgc_send_later		= ( isset( $_POST['wooposgc_send_later_check'] ) ? sanitize_text_field( $_POST['wooposgc_send_later_check'] ) : ""  );
				$wooposgc_send_later_date	= ( isset( $_POST['wooposgc_send_later_date'] ) ? sanitize_text_field( $_POST['wooposgc_send_later_date'] ) : ""  );

?><br />
				<div class="hide-on-reload">
					<input type="checkbox" name="wooposgc_send_later_check" id="wooposgc_send_later_check" <?php if ( $wooposgc_send_later == "on") { echo "checked=checked"; } ?>> <label for="wooposgc_send_later_check"><?php _e('Send Gift Card Later', 'wooposgc'); ?></label>
					<input type="text" name="wooposgc_send_later_date" id="wooposgc_send_later_date" class="input-text show-on-send-later" style="margin-bottom:5px; display:none;" placeholder="<?php _e('Enter Sending Date', 'wooposgc' ); ?>" value="<?php echo $wooposgc_send_later_date; ?>">
				</div>
			<?php
			}
		}

		public function wooposgc_add_send_later_field( $giftValue ) {
			if ( isset( $giftValue['SendLater'] ) ) {
				// Send Later
				woocommerce_wp_text_input(
					array(
						'id' 						=> 'wooposgc_send_later',
						'label' 					=> __( 'Send Later Date', 'wooposgc' ),
						'description' 				=> __( 'The date this Gift Card will be sent out, <code>YYYY-MM-DD</code>.', 'wooposgc' ),
						'class' 					=> 'date-picker short',
						'custom_attributes' 		=> array( 'pattern' => "[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" ),
						'value'						=> isset( $giftValue['SendLater'] ) ? $giftValue['SendLater'] : ''
					)
				);
			}
		}

		public function wooposgc_send_gift_cards() {

			$lastSent = get_option( 'wooposgc_check_send' );
			$date = date('Y-m-d H:i:s');

			if ( $date >= $lastSent ) {
				$tomorrow = date('Y-m-d H:i:s', strtotime($date . ' +1 day'));
				update_option( 'wooposgc_check_send', $tomorrow );
			
				$args = array( 'post_type' => 'wooposgc_giftcard', 'posts_per_page' => -1 );
				$myposts = get_posts( $args );

				foreach ( $myposts as $key => $post ) {
					$giftCard = get_post_meta( $post->ID, '_wooposgc_giftcard', true );
					if ( isset( $giftCard['SendLater'] ) ) {
						if ( ( $giftCard['sendTheEmail'] == 0 ) && ( $giftCard['SendLater'] <= date('Y-m-d') ) ) {
						
							$email = new WOOPOSGC_Giftcard_Email();
							$post = get_post( $post->ID );
							$email->sendEmail ( $post );
						
							$giftCard['sendTheEmail'] = 1;
							update_post_meta( $post->ID, '_wooposgc_giftcard', $giftCard );
						}
					}
				}
			}
		}
	}
//******************************************//

}

