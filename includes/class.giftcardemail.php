<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


class WOOPOSGC_Giftcard_Email {

	public function sendEmail ( $post ) {

		$blogname 		= wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
		$subject 		= apply_filters( 'woocommerce_email_subject_gift_card', sprintf( '[%s] %s', $blogname, __( 'Gift Card Information', 'wooposgc' ) ), $post->post_title );
		$sendEmail 		= get_bloginfo( 'admin_email' );
		$headers 		= array('Content-Type: text/html; charset=UTF-8');

		ob_start();

		$mailer 		= WC()->mailer();
		$email 			= new WOOPOSGC_Giftcard_Email();

		echo '<style >';
		wc_get_template( 'emails/email-styles.php' );
		echo '</style>';

	  	$email_heading 	= __( 'New gift card from ', 'wooposgc' ) . $blogname;
	  	$email_heading 	= apply_filters( 'wooposgc_emailSubject', $email_heading );
	  	$toEmail		= wooposgc_get_giftcard_to_email( $post->ID );

	  	$theMessage 	= $email->sendGiftcardEmail ( $post->ID );

		$theMessage 	= apply_filters( 'wooposgc_emailContents', $theMessage );

	  	echo $mailer->wrap_message( $email_heading, $theMessage );

		$message 		= ob_get_clean();
		$attachment = '';

		$mailer->send( $toEmail, $subject, $message, $headers, $attachment );

	}
 
    public function sendGiftcardEmail ( $giftCard ) {


		$expiry_date = wooposgc_get_giftcard_expiration( $giftCard );
		$date_format = get_option('date_format');
		ob_start();
		
		?>

		<div class="message">


			<?php _e( 'Dear', 'wooposgc' ); ?> <?php echo wooposgc_get_giftcard_to( $giftCard ); ?>,<br /><br />
			
			<?php $message = wooposgc_get_custom_message();
			if( $message == 'default' ) { ?>
				<?php echo wooposgc_get_giftcard_from( $giftCard ); ?> <?php _e('has gifted a', 'wooposgc' ); ?> <strong><a href="<?php bloginfo( 'url' ); ?>"><?php bloginfo( 'name' ); ?></a></strong> <?php _e( 'Gift Card for you! This card can be used for online or in-store purchases at', 'wooposgc' ); ?> <?php bloginfo( 'name' ); ?>. <br />
			<?php } else {
				echo wooposgc_get_giftcard_from( $giftCard ); ?> <?php _e('has gifted a', 'wooposgc' ); ?> <strong><a href="<?php bloginfo( 'url' ); ?>"><?php bloginfo( 'name' ); ?></a></strong> <?php _e( 'Gift Card for you!', 'wooposgc' ); ?>. <?php echo $message; ?> <br />
			<?php } ?>

			<h4><?php _e( 'Gift Card Amount', 'wooposgc' ); ?>: <?php echo wc_price( wooposgc_get_giftcard_balance( $giftCard ) ); ?></h4>
			<h4><?php _e( 'Gift Card Number', 'wooposgc' ); ?>: <?php echo get_the_title( $giftCard ); ?></h4>

			<?php
			if ( $expiry_date != "" ) {
				echo __( 'Expiration Date', 'wooposgc' ) . ': ' . date_i18n( get_option( 'date_format' ), strtotime( $expiry_date ) );
			}
			?>
		</div>

		<div style="padding-top: 10px; padding-bottom: 10px; border-top: 1px solid #ccc;">
			<?php echo wooposgc_get_giftcard_note( $giftCard ); ?>
		</div>

		<div style="padding-top: 10px; border-top: 1px solid #ccc;">
		
		<?php $instruction = wooposgc_get_custom_instructions();
		if( $instruction == 'default' ) { ?>
			<?php _e( 'Using your Gift Card is easy', 'wooposgc' ); ?>:

			<ol>
				<li><?php _e( 'Shop at', 'wooposgc' ); ?> <?php bloginfo( 'name' ); ?></li>
				<li><?php _e( 'Select "Pay with a Gift Card" during checkout.', 'wooposgc' ); ?></li>
				<li><?php _e( 'Enter your card number.', 'wooposgc' ); ?></li>
			</ol>
		</div>
		<?php } else {
			echo $instruction;
		}

		$return = ob_get_clean();
		return apply_filters( 'wooposgc_email_content_return', $return, $giftCard );

	}
}


