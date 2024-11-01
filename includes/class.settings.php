<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WC_Settings_Accounts
 */
class WOOPOSGC_Settings extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'wooposgc-giftcard';
		$this->label = __( 'Gift Cards',  'wooposgc'  );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );

		add_action( 'woocommerce_admin_field_addon_settings', array( $this, 'addon_setting' ) );
		add_action( 'woocommerce_admin_field_excludeProduct', array( $this, 'excludeProducts' ) );
	}


	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function get_sections() {

		$sections = apply_filters( 'woocommerce_add_section_giftcard', array( '' => __( 'Gift Card Options', 'wooposgc' ) ) );

		
		$wooposgc_gift_version = get_option( 'wooposgc_gift_version' );

		if ( ! $wooposgc_gift_version ) {
			// 2.0.0 is the first version to use this option so we must add it
			$wooposgc_gift_version = WOOPOSGC_VERSION;
		}

		$wooposgc_gift_version = preg_replace( '/[^0-9.].*/', '', $wooposgc_gift_version );

		

		return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
	}

	/**
	 * Output sections
	 */
	public function output_sections() {
		global $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) ) {
			return;
		}

		echo '<ul class="subsubsub">';

		$array_keys = array_keys( $sections );

		foreach ( $sections as $id => $label ) {
			echo '<li><a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
		}

		echo '</ul><br class="clear" />';
	}

	/**
	 * Output the settings
	 */
	public function output() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

 		WC_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings
	 */
	public function save() {
		global $current_section;
		
		if( !empty( $_GET['section'] ) ) {
			$settings = $this->get_settings( $current_section );
			WC_Admin_Settings::save_fields( $settings );
		} else {
			parent::save();
			$this->run_update();
		}		
	}


	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		$options = '';
		if( $current_section == '' ) {

			$options = apply_filters( 'wooposgc_giftcard_settings', array(

				array( 'title' 		=> __( 'Processing Options',  'wooposgc'  ), 'type' => 'title', 'id' => 'giftcard_processing_options_title' ),

				array(
					'title'         => __( 'Display on Cart?',  'wooposgc'  ),
					'desc'          => __( 'Display the giftcard form on the cart page.',  'wooposgc'  ),
					'id'            => 'wooposgc_enable_giftcard_cartpage',
					'default'       => 'yes',
					'type'          => 'checkbox',
					'autoload'      => false
				),

				array(
					'title'         => __( 'Display on Checkout?',  'wooposgc'  ),
					'desc'          => __( 'Display the giftcard form on the checkout page.',  'wooposgc'  ),
					'id'            => 'wooposgc_enable_giftcard_checkoutpage',
					'default'       => 'yes',
					'type'          => 'checkbox',
					'autoload'      => false
				),


				array(
					'title'         => __( 'Require Recipient Information?',  'wooposgc'  ),
					'desc'          => __( 'Requires that your customers enter a name and email when purchasing a Gift Card.',  'wooposgc'  ),
					'id'            => 'wooposgc_enable_giftcard_info_requirements',
					'default'       => 'no',
					'type'          => 'checkbox',
					'autoload'      => true
				),
				array(
					'title'         => __( 'Customize Add to Cart?',  'wooposgc'  ),
					'desc'          => __( 'Change Add to cart label and disable add to cart from product list.',  'wooposgc'  ),
					'id'            => 'wooposgc_enable_addtocart',
					'default'       => 'no',
					'type'          => 'checkbox',
					'autoload'      => false
				),
				array(
					'title'         => __( 'Physical Card?',  'wooposgc'  ),
					'desc'          => __( 'Select this if you would like to offer physical gift cards.',  'wooposgc'  ),
					'id'            => 'wooposgc_enable_physical',
					'default'       => 'no',
					'type'          => 'checkbox',
					'autoload'      => false
				),
				array(
					'title'         => __( 'One Time Use Cards?',  'wooposgc'  ),
					'desc'          => __( 'Select this if you want cards to be disabled after first use.',  'wooposgc'  ),
					'id'            => 'wooposgc_enable_one_time_use',
					'default'       => 'no',
					'type'          => 'checkbox',
					'autoload'      => false
				),
				array(
					'title'         => __( 'Allow Multiples',  'wooposgc'  ),
					'desc'          => __( 'Select this if you would like to allow customers to purchase multiples of one card.',  'wooposgc'  ),
					'id'            => 'wooposgc_enable_multiples',
					'default'       => 'no',
					'type'          => 'checkbox',
					'autoload'      => false
				),
				array(
					'title'         => __( 'Disable Coupons',  'wooposgc'  ),
					'desc'          => __( 'Disable coupons when purchaseing a gift card.',  'wooposgc'  ),
					'id'            => 'wooposgc_woocommerce_disable_coupons',
					'default'       => 'no',
					'type'          => 'checkbox',
					'autoload'      => false
				),
				array(
					'title'         => __( 'Disable Notes Field',  'wooposgc'  ),
					'desc'          => __( 'Disable notes field when purchaseing a gift card.',  'wooposgc'  ),
					'id'            => 'wooposgc_woocommerce_disable_notes',
					'default'       => 'no',
					'type'          => 'checkbox',
					'autoload'      => false
				),


				array( 'type' => 'sectionend', 'id' => 'account_registration_options'),

				array( 'title' 		=> __( 'Gift Card Uses',  'wooposgc'  ), 'type' => 'title', 'id' => 'giftcard_products_title' ),

				array(
					'title'         => __( 'Shipping',  'wooposgc'  ),
					'desc'          => __( 'Allow customers to pay for shipping with their gift card.',  'wooposgc'  ),
					'id'            => 'wooposgc_enable_giftcard_charge_shipping',
					'default'       => 'no',
					'type'          => 'checkbox',
					'autoload'      => true
				),

				array(
					'title'         => __( 'Tax',  'wooposgc'  ),
					'desc'          => __( 'Allow customers to pay for tax with their gift card.',  'wooposgc'  ),
					'id'            => 'wooposgc_enable_giftcard_charge_tax',
					'default'       => 'no',
					'type'          => 'checkbox',
					'autoload'      => true
				),

				array(
					'title'         => __( 'Fee',  'wooposgc'  ),
					'desc'          => __( 'Allow customers to pay for fees with their gift card.',  'wooposgc'  ),
					'id'            => 'wooposgc_enable_giftcard_charge_fee',
					'default'       => 'no',
					'type'          => 'checkbox',
					'autoload'      => true
				),
				
				array(
					'title'         => __( 'Other Gift Cards',  'wooposgc'  ),
					'desc'          => __( 'Allow customers to pay for gift cards with their existing gift card.',  'wooposgc'  ),
					'id'            => 'wooposgc_enable_giftcard_charge_giftcard',
					'default'       => 'yes',
					'type'          => 'checkbox',
					'autoload'      => true
				),

				array( 'type' => 'excludeProduct' ),

				array( 'type' => 'sectionend', 'id' => 'uses_giftcard_options'),

				array( 'title' 		=> __( 'Gift Card Email',  'wooposgc'  ), 'type' => 'title', 'id' => 'giftcard_email_title' ),

				array(
					'title'         => __( 'Email Message',  'wooposgc'  ),
					'desc'          => __( 'Change the email message that gets sent with your gift card.',  'wooposgc'  ),
					'id'            => 'wooposgc_enable_giftcard_custom_message',
					'default'       => '',
					'css'     		=> 'width:100%; height: 65px;',
					'type'          => 'textarea',
					'autoload'      => true
				),

				array(
					'title'         => __( 'Redemption Instructions',  'wooposgc'  ),
					'desc'          => __( 'Enter how people will redeem their gift cards.',  'wooposgc'  ),
					'id'            => 'wooposgc_enable_giftcard_redemption_info',
					'default'       => '',
					'css'     		=> 'width:100%; height: 65px;',
					'type'          => 'textarea',
					'autoload'      => true
				),

				array( 'type' => 'sectionend', 'id' => 'email_giftcard_options'),

				array( 'title' 		=> __( 'Product Options',  'wooposgc'  ), 'type' => 'title', 'id' => 'giftcard_products_options_title' ),

				array(
					'name'     => __( 'To', 'wooposgc' ),
					'desc'     => __( 'This is the value that will display before a gift card number.', 'wooposgc' ),
					'id'       => 'wooposgc_giftcard_to',
					'std'      => 'To', // WooCommerce < 2.0
					'default'  => 'To', // WooCommerce >= 2.0
					'type'     => 'text',
					'desc_tip' =>  true,
				),

				array(
					'name'     => __( 'To Email', 'wooposgc' ),
					'desc'     => __( 'This is the value that will display before a gift card number.', 'wooposgc' ),
					'id'       => 'wooposgc_giftcard_toEmail',
					'std'      => 'Send To', // WooCommerce < 2.0
					'default'  => 'Send To', // WooCommerce >= 2.0
					'type'     => 'text',
					'desc_tip' =>  true,
				),

				array(
					'name'     => __( 'Note Option', 'wooposgc' ),
					'desc'     => __( 'This will change the placeholder field for the gift card note.', 'wooposgc' ),
					'id'       => 'wooposgc_giftcard_note',
					'std'      => 'Enter your note here.', // WooCommerce < 2.0
					'default'  => 'Enter your note here.', // WooCommerce >= 2.0
					'type'     => 'text',
					'desc_tip' =>  true,
				),

				array(
					'name'     => __( 'Address', 'wooposgc' ),
					'desc'     => __( 'This will change the placeholder field for the address field.', 'wooposgc' ),
					'id'       => 'wooposgc_giftcard_address',
					'std'      => 'Address', // WooCommerce < 2.0
					'default'  => 'Address', // WooCommerce >= 2.0
					'type'     => 'text',
					'desc_tip' =>  true,
				),

				array(
					'name'     => __( 'Reload Gift Card', 'wooposgc' ),
					'desc'     => __( 'This will change the placeholder field for the reloading option.', 'wooposgc' ),
					'id'       => 'wooposgc_giftcard_reload_card',
					'std'      => 'Cart Number', // WooCommerce < 2.0
					'default'  => 'Cart Number', // WooCommerce >= 2.0
					'type'     => 'text',
					'desc_tip' =>  true,
				),

				array(
					'name'     => __( 'Gift Card Button Text', 'wooposgc' ),
					'desc'     => __( 'This is the text that will be displayed on the button to customize the information.', 'wooposgc' ),
					'id'       => 'wooposgc_giftcard_button',
					'std'      => 'Customize', // WooCommerce < 2.0
					'default'  => 'Customize', // WooCommerce >= 2.0
					'type'     => 'text',
					'desc_tip' =>  true,
				),

				array( 'type' => 'sectionend', 'id' => 'account_registration_options'),

			));
		}
		return apply_filters ('get_giftcard_settings', $options, $current_section );
	}

	public function excludeProducts() {
		if( isset( $_POST['wooposgc_giftcard_exclude_product_ids'] ) ) 
			update_option( 'wooposgc_giftcard_exclude_product_ids', sanitize_text_field($_POST['wooposgc_giftcard_exclude_product_ids'] ) );
			
		?>
			<tr valign="top" class="">
				<th class="titledesc" scope="row">
					<?php _e( 'Exclude products', 'wooposgc' ); ?>
					<img class="help_tip" data-tip='<?php _e( 'Products which gift cards can not be used on', 'wooposgc' ); ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
				</th>
					<td class="forminp forminp-checkbox">
					<fieldset>
						<input type="hidden" class="wc-product-search" data-multiple="true" style="width: 50%;" name="wooposgc_giftcard_exclude_product_ids" data-placeholder="<?php _e( 'Search for a product&hellip;', 'wooposgc' ); ?>" data-action="woocommerce_json_search_products_and_variations" data-selected="<?php
							$product_ids = array_filter( array_map( 'absint', explode( ',', get_option( 'wooposgc_giftcard_exclude_product_ids' ) ) ) );
							$json_ids    = array();

							foreach ( $product_ids as $product_id ) {
								$product = wc_get_product( $product_id );
								$json_ids[ $product_id ] = wp_kses_post( $product->get_formatted_name() );
							}

							echo esc_attr( json_encode( $json_ids ) );
						?>" value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>" />
					</fieldset>
				</td>
			</tr>
		<?php

	}

	public function run_update() {
		$wooposgc_gift_version = get_option( 'wooposgc_gift_version', false);
		if ( ! $wooposgc_gift_version ) {
			// 1.3 is the first version to use this option so we must add it
			$wooposgc_gift_version = WOOPOSGC_VERSION;
			add_option( 'wooposgc_gift_version', $wooposgc_gift_version );
		} else {
			update_option( 'wooposgc_gift_version', WOOPOSGC_VERSION );	
		}


		do_action( 'wooposgc_add_updates' );

		wp_redirect( admin_url() . 'edit.php?post_type=wooposgc_giftcard' ); 
		exit; 

	}

	

}
//return new WOOPOSGC_Settings();