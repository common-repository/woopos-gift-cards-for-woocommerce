<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Cash on Delivery Gateway
 *
 * Provides a In Store Credit Payment Gateway.
 *
 * @class 		WC_Gateway_ISC
 * @extends		WC_Payment_Gateway
 * @version		2.1.0
 * @package		WooCommerce/Classes/Payment
 * @author 		WooThemes
 */

if( class_exists( 'Wooposgc_Giftcards' ) && class_exists( 'WooCommerce' ) ) {

	class WC_Gateway_ISC extends WC_Payment_Gateway {

	    /**
	     * Constructor for the gateway.
	     */
		public function __construct() {
			$this->id                 = 'isc';
			$this->icon               = apply_filters( 'woocommerce_isc_icon', '' );
			$this->method_title       = __( 'In Store Credit', 'wooposgc' );
			$this->method_description = __( 'Have your customers pay with an available in store credit.', 'wooposgc' );
			$this->has_fields         = false;

			// Load the settings
			$this->init_form_fields();
			$this->init_settings();

			// Get settings
			$this->title              	= $this->get_option( 'title' );
			$this->description        	= $this->get_option( 'description' );
			$this->instructions       	= $this->get_option( 'instructions', $this->description );
			$this->enable_for_methods 	= $this->get_option( 'enable_for_methods', array() );
			$this->enable_for_virtual 	= $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes' ? true : false;
			$this->pay_for_tax 			= $this->get_option( 'pay_for_tax', 'yes' ) === 'yes' ? true : false;
			$this->pay_for_shipping   	= $this->get_option( 'pay_for_shipping', 'yes' ) === 'yes' ? true : false;

			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_isc', array( $this, 'thankyou_page' ) );

	    	// Customer Emails
	    	add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		}

	    /**
	     * Initialise Gateway Settings Form Fields
	     */
	    public function init_form_fields() {
	    	$shipping_methods = array();

	    	if ( is_admin() )
		    	foreach ( WC()->shipping->load_shipping_methods() as $method ) {
			    	$shipping_methods[ $method->id ] = $method->get_title();
		    	}

	    	$this->form_fields = array(
				'enabled' => array(
					'title'       => __( 'Enable In Store Credit', 'wooposgc' ),
					'label'       => __( 'Enable the in store credit option', 'wooposgc' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),
				'title' => array(
					'title'       => __( 'Title', 'wooposgc' ),
					'type'        => 'text',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'wooposgc' ),
					'default'     => __( 'In Store Credit', 'wooposgc' ),
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => __( 'Description', 'wooposgc' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your website.', 'wooposgc' ),
					'default'     => __( 'Pay with in store credit.', 'wooposgc' ),
					'desc_tip'    => true,
				),
				'enable_for_methods' => array(
					'title'             => __( 'Enable for shipping methods', 'wooposgc' ),
					'type'              => 'multiselect',
					'class'             => 'chosen_select',
					'css'               => 'width: 450px;',
					'default'           => '',
					'description'       => __( 'If isc is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'wooposgc' ),
					'options'           => $shipping_methods,
					'desc_tip'          => true,
					'custom_attributes' => array(
						'data-placeholder' => __( 'Select shipping methods', 'wooposgc' )
					)
				),
				'enable_for_virtual' => array(
					'title'             => __( 'Enable for virtual orders', 'wooposgc' ),
					'label'             => __( 'Enable isc if the order is virtual', 'wooposgc' ),
					'type'              => 'checkbox',
					'default'           => 'yes'
				),
				'pay_for_tax' => array(
					'title'             => __( 'Enable for tax', 'wooposgc' ),
					'label'             => __( 'Enable isc payment on tax', 'wooposgc' ),
					'type'              => 'checkbox',
					'default'           => 'yes'
				),
				'pay_for_shipping' => array(
					'title'             => __( 'Enable for shipping', 'wooposgc' ),
					'label'             => __( 'Enable isc payment on shipping', 'wooposgc' ),
					'type'              => 'checkbox',
					'default'           => 'yes'
				)
	 	   );
	    }

		/**
		 * Check If The Gateway Is Available For Use
		 *
		 * @return bool
		 */
		public function is_available() {
			$order = null;

			if ( ! $this->enable_for_virtual ) {
				if ( WC()->cart && ! WC()->cart->needs_shipping() ) {
					return false;
				}

				if ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
					$order_id = absint( get_query_var( 'order-pay' ) );
					$order    = wc_get_order( $order_id );

					// Test if order needs shipping.
					$needs_shipping = false;

					if ( 0 < sizeof( $order->get_items() ) ) {
						foreach ( $order->get_items() as $item ) {
							$_product = $order->get_product_from_item( $item );

							if ( $_product->needs_shipping() ) {
								$needs_shipping = true;
								break;
							}
						}
					}

					$needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );

					if ( $needs_shipping ) {
						return false;
					}
				}
			}

			if ( ! empty( $this->enable_for_methods ) ) {

				// Only apply if all packages are being shipped via local pickup
				$chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

				if ( isset( $chosen_shipping_methods_session ) ) {
					$chosen_shipping_methods = array_unique( $chosen_shipping_methods_session );
				} else {
					$chosen_shipping_methods = array();
				}

				$check_method = false;

				if ( is_object( $order ) ) {
					if ( $order->shipping_method ) {
						$check_method = $order->shipping_method;
					}

				} elseif ( empty( $chosen_shipping_methods ) || sizeof( $chosen_shipping_methods ) > 1 ) {
					$check_method = false;
				} elseif ( sizeof( $chosen_shipping_methods ) == 1 ) {
					$check_method = $chosen_shipping_methods[0];
				}

				if ( ! $check_method ) {
					return false;
				}

				$found = false;

				foreach ( $this->enable_for_methods as $method_id ) {
					if ( strpos( $check_method, $method_id ) === 0 ) {
						$found = true;
						break;
					}
				}

				if ( ! $found ) {
					return false;
				}
			}

			$user_ID = get_current_user_id();
			$isc = (float) get_user_meta( $user_ID, '_wooposgc_isc', true );

			if ( ($isc > 0 ) && ! isset (WC()->session->wooposgc_isc_amount) )
				return parent::is_available();
		}

		/**
		 * get_icon function.
		 *
		 * @return string
		 */
		public function get_icon() {
			$link = null;

			$user_ID = get_current_user_id();

			$isc = (float) get_user_meta( $user_ID, '_wooposgc_isc', true );

			$currentISC = wc_price( $isc );

			$what_is_paypal = '<span class="wooposgc_isc_amount" stlye="font-size: 0.8em; float: right;">( ' . $currentISC . ' )</span>';

			return apply_filters( 'woocommerce_gateway_icon', $icon_html . $what_is_paypal, $this->id );
		}

	    /**
	     * Process the payment and return the result
	     *
	     * @param int $order_id
	     * @return array
	     */
		public function process_payment( $order_id ) {
			
			$order = wc_get_order( $order_id );

			unset( WC()->session->wooposgc_isc_remaining, WC()->session->wooposgc_isc_amount );
			$customer = get_post_meta( $order_id, '_customer_user', true );
	 		$isc = (float) get_user_meta( $customer, '_wooposgc_isc', true );
			
	 		$orderTotal = WC()->cart->total;
	 		$orderSubTotal = WC()->cart->subtotal;
	 		
	 		$orderTax = WC()->cart->tax_total;
	 		$orderShipping = WC()->cart->shipping_total;

	 		$amountToPay = $orderSubTotal;

	 		$reload = 'false';
	 		$redirect = $this->get_return_url( $order );




	 		

			if ( $this->pay_for_tax )
				$amountToPay = $amountToPay + $orderTax;

			if ( $this->pay_for_shipping )
				$amountToPay = $amountToPay + $orderShipping;

			
			if ( $amountToPay == $orderTotal ){
				$reload = 'true';
				$checkoutPage = WC()->cart->get_checkout_url();
			}





			if ( $isc <= $amountToPay ) {
				$amountRemaining = $amountToPay - $orderTotal;

				WC()->session->wooposgc_isc_remaining = $amountRemaining;
				WC()->session->wooposgc_isc_amount = $amountToPay;

				
				
				
				
			} else {
				$amountofISC = $isc - $amountToPay;

				update_usermeta( $customer, '_wooposgc_isc', $amountofISC );

				// Mark as processing (payment won't be taken until delivery)
				$order->update_status( 'processing', __( 'Payment to be made upon delivery.', 'wooposgc' ) );

				// Reduce stock levels
				$order->reduce_order_stock();

				// Remove cart
				WC()->cart->empty_cart();


				
				

			}



			// Return thankyou redirect
			return array(
				'result' 	=> 'success',
				'redirect'	=> $redirect,
				'reload'	=> $reload
			);


		}

	    /**
	     * Output for the order received page.
	     */
		public function thankyou_page() {
			if ( $this->instructions ) {
	        	echo wpautop( wptexturize( $this->instructions ) );
			}
		}

	    /**
	     * Add content to the WC emails.
	     *
	     * @access public
	     * @param WC_Order $order
	     * @param bool $sent_to_admin
	     * @param bool $plain_text
	     */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
			if ( $this->instructions && ! $sent_to_admin && 'isc' === $order->payment_method ) {
				echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
			}
		}
	}
}