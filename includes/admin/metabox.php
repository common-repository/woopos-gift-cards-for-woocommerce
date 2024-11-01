<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly



if ( is_admin()  ) {
    add_action( 'load-post.php', 'call_WOOPOSGC_Gift_Card_Meta' );
    add_action( 'load-post-new.php', 'call_WOOPOSGC_Gift_Card_Meta' );

}

/** 
 * The Class.
 */
class WOOPOSGC_Gift_Card_Meta {

	/**
	 * Hook into the appropriate actions when the class is constructed.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'wooposgc_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save' ) );
		
		if( isset( $_GET['post_type'] ) ) {
			if ( $_GET['post_type'] == 'wooposgc_giftcard' )
				add_action( 'post_submitbox_misc_actions', array( $this, 'wooposgc_giftcard_title' ) );
		}
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save( $post_id ) {
		global $post, $wpdb;

		// Check if our nonce is set.
		if ( ! isset( $_POST['woocommerce_giftcard_nonce'] ) )
			return $post_id;

		$nonce = $_POST['woocommerce_giftcard_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'woocommerce_save_data' ) )
			return $post_id;

		// If this is an autosave, our form has not been submitted,
                //     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		// Check the user's permissions.
		if ( 'wooposgc_giftcard' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
	
		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}

		/* OK, its safe for us to save the data now. */

		$newGift = new WOOPOSGC_Giftcard();
		$newGift->createCard( wc_clean( $_POST ) );

		do_action( 'wooposgc_woocommerce_options' );
		do_action( 'wooposgc_woocommerce_after_save', $post_id );
	}


	/**	
	 * Sets up the new meta box for the creation of a gift card.
	 * Removes the other three Meta Boxes that are not needed.
	 *
	 */
	public function wooposgc_meta_boxes() {
		global $post;

		add_meta_box(
			'wooposgc-woocommerce-data',
			__( 'Gift Card Data', 'wooposgc' ),
			array( $this, 'wooposgc_meta_box'),
			'wooposgc_giftcard',
			'normal',
			'high'
		);

		$data = get_post_meta( $post->ID );

		if ( isset( $data['wooposgc_id'] ) ) 
			if ( $data['wooposgc_id'][0] <> '' )
				add_meta_box(
					'wooposgc-order-data',
					__( 'Gift Card Information', 'wooposgc' ),
					array( $this, 'wooposgc_info_meta_box'),
					'shop_order',
					'side',
					'default'
				);

		//if ( ! isset( $_GET['action'] ) ) 
		//	remove_post_type_support( 'wooposgc_giftcard', 'title' );
		
		if ( isset ( $_GET['action'] ) ) {
			add_meta_box(
				'wooposgc-more-options',
				__( 'Additional Card Options', 'wooposgc' ),
				array( $this, 'wooposgc_options_meta_box'),
				'wooposgc_giftcard',
				'side',
				'low'
			);

			add_meta_box(
				'wooposgc-usage-data',
				__( 'Card Usage Data', 'wooposgc' ),
				array( $this, 'wooposgc_giftcard_usage_data'),
				'wooposgc_giftcard',
				'side',
				'low'
			);

		}

		remove_meta_box( 'woothemes-settings', 'wooposgc_giftcard' , 'normal' );
		remove_meta_box( 'commentstatusdiv', 'wooposgc_giftcard' , 'normal' );
		remove_meta_box( 'commentsdiv', 'wooposgc_giftcard' , 'normal' );
		remove_meta_box( 'slugdiv', 'wooposgc_giftcard' , 'normal' );
	}

	/**
	 * Creates the Giftcard Meta Box in the admin control panel when in the Giftcard Post Type.  Allows you to create a giftcard manually.
	 * @param  [type] $post
	 * @return [type]
	 */
	public function wooposgc_meta_box( $post ) {
		global $woocommerce;

		wp_nonce_field( 'woocommerce_save_data', 'woocommerce_giftcard_nonce' );

		$giftValue = get_post_meta( $post->ID, '_wooposgc_giftcard', true );

		?>
		<style type="text/css">
			#edit-slug-box, #minor-publishing-actions { display:none }

			.form-field input, .form-field textarea { width:100%;}

			input[type="checkbox"], input[type="radio"] { float: left; width:16px;}

		</style>

		<div id="giftcard_options" class="panel woocommerce_options_panel">
		<?php
		
		do_action( 'wooposgc_woocommerce_options_before_sender' );

		// Description
		woocommerce_wp_textarea_input(
			array(
				'id' 			=> 'wooposgc_description',
				'label'			=> __( 'Gift Card description', 'wooposgc' ),
				'placeholder' 	=> '',
				'description' 	=> __( 'Optionally enter a description for this gift card for your reference.', 'wooposgc' ),
				'value'			=> isset( $giftValue['description'] ) ? $giftValue['description'] : ''
			)
		);
		
		do_action( 'wooposgc_woocommerce_options_after_description' );

		echo '<h2>' . __('Who are you sending this to?',  'wooposgc' ) . '</h2>';
		// To
		woocommerce_wp_text_input(
			array(
				'id' 			=> 'wooposgc_to',
				'label' 		=> __( 'To', 'wooposgc' ),
				'placeholder' 	=> '',
				'description' 	=> __( 'Who is getting this gift card.', 'wooposgc' ),
				'value'			=> isset( $giftValue['to'] ) ? $giftValue['to'] : ''
			)
		);
		// To Email
		woocommerce_wp_text_input(
			array(
				'id' 			=> 'wooposgc_email_to',
				'type' 			=> 'email',
				'label' 		=> __( 'Email To', 'wooposgc' ),
				'placeholder' 	=> '',
				'description' 	=> __( 'What email should we send this gift card to.', 'wooposgc' ),
				'value'			=> isset( $giftValue['toEmail'] ) ? $giftValue['toEmail'] : ''
			)
		);

		// From
		woocommerce_wp_text_input(
			array(
				'id' 			=> 'wooposgc_from',
				'label' 		=> __( 'From', 'wooposgc' ),
				'placeholder' 	=> '',
				'description' 	=> __( 'Who is sending this gift card.', 'wooposgc' ),
				'value'			=> isset( $giftValue['from'] ) ? $giftValue['from'] : ''
			)
		);
		// From Email
		woocommerce_wp_text_input(
			array(
				'id' 			=> 'wooposgc_email_from',
				'type'	 		=> 'email',
				'label' 		=> __( 'Email From', 'wooposgc' ),
				'placeholder' 	=> '',
				'description' 	=> __( 'What email account is sending this gift card.', 'wooposgc' ),
				'value'			=> isset( $giftValue['fromEmail'] ) ? $giftValue['fromEmail'] : ''
			)
		);
		
		do_action( 'wooposgc_woocommerce_options_after_sender' );

		echo '</div><div class="panel woocommerce_options_panel">';

		echo '<h2>' . __('Personalize it',  'wooposgc' ) . '</h2>';
		
		do_action( 'wooposgc_woocommerce_options_before_personalize' );
		
		// Amount
		woocommerce_wp_text_input(
			array(
				'id'     					=> 'wooposgc_amount',
				'label'   					=> __( 'Gift Card Amount', 'wooposgc' ),
				'placeholder'  				=> __( '0.00', 'wooposgc'),
				'description'  				=> __( 'Value of the Gift Card.', 'wooposgc' ),
				'type'    					=> 'number',
				'custom_attributes' 		=> array( 'step' => 'any', 'min' => '0' ),
				'value'						=> isset( $giftValue['amount'] ) ? $giftValue['amount'] : ''
			)
		);
		if ( isset( $_GET['action']  ) ) {
			if ( $_GET['action'] == 'edit' ) {
				// Remaining Balance
				woocommerce_wp_text_input(
					array(
						'id'    			=> 'wooposgc_balance',
						'label'    			=> __( 'Gift Card Balance', 'wooposgc' ),
						'placeholder'  		=> __( '0.00', 'wooposgc'),
						'description'  		=> __( 'Remaining Balance of the Gift Card.', 'wooposgc' ),
						'type'    			=> 'number',
						'custom_attributes' => array( 'step' => 'any', 'min' => '0' ),
						'value'				=> isset( $giftValue['balance'] ) ? $giftValue['balance'] : ''
					)
				);
			}
		}
		// Notes
		woocommerce_wp_textarea_input(
			array(
				'id' 						=> 'wooposgc_note',
				'label' 					=> __( 'Gift Card Note', 'wooposgc' ),
				'description' 				=> __( 'Enter a message to your customer.', 'wooposgc' ),
				'class' 					=> 'short',
				'value'						=> isset( $giftValue['note'] ) ? $giftValue['note'] : ''
				
			)
		);

		// Expiry date
		woocommerce_wp_text_input(
			array(
				'id' 						=> 'wooposgc_expiry_date',
				'label' 					=> __( 'Expiry date', 'wooposgc' ),
				'placeholder' 				=> _x( 'Never expire', 'placeholder', 'wooposgc' ),
				'description' 				=> __( 'The date this Gift Card will expire, <code>YYYY-MM-DD</code>.', 'wooposgc' ),
				'class' 					=> 'date-picker short',
				'custom_attributes' 		=> array( 'pattern' => "[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" ),
				'value'						=> isset( $giftValue['expiry_date'] ) ? $giftValue['expiry_date'] : ''
			)
		);

		do_action( 'wooposgc_woocommerce_options_after_personalize', $giftValue );


		echo '</div>';
	}



	/**
	 * Creates the Giftcard Regenerate Meta Box in the admin control panel when in the Giftcard Post Type.  Allows you to click a button regenerate the number.
	 * @param  [type] $post
	 * @return [type]
	 */
	public function wooposgc_options_meta_box( $post ) {
		global $woocommerce;

		wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );	
		
		echo '<div id="giftcard_regenerate" class="panel woocommerce_options_panel">';
		echo '    <div class="options_group">';

		if( $post->post_status <> 'zerobalance' ) {
			// Regenerate the Card Number
			woocommerce_wp_checkbox( array( 'id' => 'wooposgc_resend_email', 'label' => __( 'Send Gift Card Email', 'wooposgc' ) ) );

			// Regenerate the Card Number
			woocommerce_wp_checkbox( array( 'id' => 'wooposgc_regen_number', 'label' => __( 'Regenerate Card Number', 'wooposgc' ) ) );

			do_action( 'wooposgc_add_more_options' );

		} else {
			_e( 'No additional options available. Zero balance', 'wooposgc' );

			
		}

		echo '    </div>';
		echo '</div>';

	}



	public function wooposgc_info_meta_box( $post ) {
		global $wpdb;
		
		$data = get_post_meta( $post->ID );

		$orderCardNumbers 	= wooposgc_get_order_card_numbers( $post->ID );
		$orderCardBalance 	= wooposgc_get_order_card_balance( $post->ID );
		$orderCardPayment 	= wooposgc_get_order_card_payment( $post->ID );
		$isAlreadyRefunded	= wooposgc_get_order_refund_status( $post->ID );

		foreach ($orderCardNumbers as $key => $orderCardNumber ) {
			echo '<div id="giftcard_regenerate" class="panel woocommerce_options_panel">';
			echo '    <div class="options_group">';
				echo '<ul>';
					if ( isset( $orderCardNumber ) )
						echo '<li>' . __( 'Gift Card #:', 'wooposgc' ) . ' ' . esc_attr( $orderCardNumber ) . '</li>';

					if ( isset( $orderCardPayment ) )
						echo '<li>' . __( 'Payment:', 'wooposgc' ) . ' ' . wc_price( $orderCardPayment[ $key ] ) . '</li>';

					if ( isset( $orderCardBalance ) )
						echo '<li>' . __( 'Balance remaining:', 'wooposgc' ) . ' ' . wc_price( $orderCardBalance[ $key ] ) . '</li>';

				echo '</ul>';

				$giftcard_found = wooposgc_get_giftcard_by_code( $orderCardNumber );

				if ( $giftcard_found ) {
					echo '<div>';
						$link = 'post.php?post=' . $giftcard_found . '&action=edit';
						echo '<a href="' . admin_url( $link ) . '">' . __('Access Gift Card', 'wooposgc') . '</a>';
						
						if( ! empty( $isAlreadyRefunded[ $key] ) )
							echo  '<br /><span style="color: #dd0000;">' . __( 'Gift card refunded ', 'wooposgc' ) . ' ' . wc_price( $orderCardPayment[ $key ] ) . '</span>';
					echo '</div>';
				
				}

			echo '    </div>';
			echo '</div>';
		}
	}

	

	// Meta box with gift card used on the order
	public function wooposgc_giftcard_usage_data( $post ) { ?>
		<div id="giftcard_usage" class="panel woocommerce_options_panel">
		<?php
		$giftcardDecreaseIDs = get_post_meta( $post->ID, 'wooposgc_existingOrders_id', true );
		$giftcardReloads = get_post_meta( $post->ID, '_wooposgc_card_reloads', true );

		$activity = 0;

		if( ! empty($giftcardDecreaseIDs) ) {
			$activity = 1;
			?>
			<div class="options_group">
		
				<?php 
				foreach ($giftcardDecreaseIDs as $giftID ) {
					$giftcardIDS = wooposgc_get_order_card_ids( $giftID );
					$giftcardPayments = wooposgc_get_order_card_payment( $giftID );
					$giftcardBalances = wooposgc_get_order_card_balance( $giftID );
					if( isset($GLOBALS['WOOCS']) ){
						$currencies = $GLOBALS['WOOCS']->get_currencies();
						if( !empty($currencies) ){
							$currency = get_post_meta( $giftID, 'wooposgc_currency', true );
							$default_currency = get_post_meta( $giftID, 'wooposgc_default_currency', true );
							if( isset($currencies[$currency]) && !empty($currencies[$currency]) ){
								$currency_symbol = $currencies[$currency]['symbol'];
							}
							if( $currency != $default_currency && isset($currencies[$default_currency]) && !empty($currencies[$default_currency]) ){
								$default_currency_symbol = $currencies[$default_currency]['symbol'];
								$giftcardPayments_raw = get_post_meta( $giftID, 'wooposgc_payment_raw', true );
								$giftcardBalances_raw = get_post_meta( $giftID, 'wooposgc_balance_raw', true );
							}
						}
					}
					//$giftcarBalance -= $giftcardPayment;
					$orederLink = admin_url( 'post.php?post=' . $giftID . '&action=edit' );

				
					foreach ($giftcardPayments as $key => $giftcardPayment) {
						if ( $giftcardIDS[ $key ] == $post->ID ) {
							?>
							<div class="box-inside">
								<p>
									<strong><?php _e( 'Order Number:', 'wooposgc' ); ?></strong>&nbsp;
									<span><a href="<?php echo $orederLink; ?>"><?php echo esc_attr( $giftID ); ?></a></span>
									<br />
									<strong><?php _e( 'Amount Used:', 'wooposgc' ); ?></strong>&nbsp;
									<span><?php echo ( isset($currency_symbol) ? $currency_symbol . $giftcardPayment : wc_price($giftcardPayment) ) . ( isset($default_currency_symbol) ? " ({$default_currency_symbol}{$giftcardPayments_raw[$key]})" : '' ); ?></span>
									<br />
									<strong><?php _e( 'Card Balance After Order:', 'wooposgc' ); ?></strong>&nbsp;
									<span><?php echo ( isset($currency_symbol) ? $currency_symbol . $giftcardBalances[$key] : wc_price($giftcardBalances[$key]) ) . ( isset($default_currency_symbol) ? " ({$default_currency_symbol}{$giftcardBalances_raw[$key]})" : ''); ?></span>
								</p>
							</div>
							<?php 
						}
					}
					unset($currency_symbol, $default_currency_symbol, $giftcardPayments_raw, $giftcardBalances_raw);
				}
				?>
			</div>
			<?php
		} 

		if ( ! empty($giftcardReloads) ) {
			$activity = 1;
			?>
			<div class="options_group">
				<?php foreach ($giftcardReloads as $giftIncrease ) { 
					$orederLink = admin_url( 'post.php?post=' . $giftIncrease["Order"] . '&action=edit' );
					?>
					
					<div class="box-inside">
						<p>
							<strong><?php _e( 'Order Number:', 'wooposgc' ); ?></strong>&nbsp;
							<span><a href="<?php echo $orederLink; ?>"><?php echo esc_attr( $giftIncrease["Order"] ); ?></a></span>
							<br />
							<strong><?php _e( 'Card Balance Increased:', 'wooposgc' ); ?></strong>&nbsp;
							<span><?php echo wc_price( $giftIncrease["Amount"] ); ?></span>
						</p>
					</div>
				<?php } ?>
			</div>
		<?php
		

		}

		if ($activity == 0 ) {
			?>
				<div class="options_group" style="text-align: center;">
				<strong><?php _e( 'Gift card has not been used.', 'wooposgc' ); ?></strong>

				</div>
			
			<?php
		}
		?>
		</div>
		<?php
	}


	// Allows you to create a gift card number manually
	public function wooposgc_giftcard_title( ) {
		?>
		<div class="misc-pub-section curtime misc-pub-cardnumber">
			<span class="dashicons dashicons-cart" style="color: #82878c;"></span>
			<span id="awards"><a class="showTitle" style="cursor: pointer; margin-left: 4px;"><?php _e( 'Manually Create Card Number', 'wooposgc'); ?></a></span>
		</div>
		<?php
	}

}



/**
 * Calls the class on the post edit screen.
 */
function call_WOOPOSGC_Gift_Card_Meta() {
    	new WOOPOSGC_Gift_Card_Meta();
}



