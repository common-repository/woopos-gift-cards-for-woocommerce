<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


/**
 * Gift Card Handler Class
 *
 * @since       1.0.0
 */
class WOOPOSGC_Giftcard {

    public $giftcard;

     public function __construct(  ) {

    }


    // Function to create the gift card
    public function createCard( $giftInformation ) {
        global $wpdb;

        $giftCard['sendTheEmail'] = 0;

        if ( isset( $giftInformation['wooposgc_description'] ) ) {
            $giftCard['description']    = wc_clean( $giftInformation['wooposgc_description'] );

        }
        if ( isset( $giftInformation['wooposgc_to'] ) ) {
            $giftCard['to'] = wc_clean( $giftInformation['wooposgc_to'] );

        }
        if ( isset( $giftInformation['wooposgc_email_to'] ) ) {
            $giftCard['toEmail']        = wc_clean( $giftInformation['wooposgc_email_to'] );

        }
        if ( isset( $giftInformation['wooposgc_from'] ) ) {
            $giftCard['from']           = wc_clean( $giftInformation['wooposgc_from'] );
        }
        if ( isset( $giftInformation['wooposgc_email_from'] ) ) {
            $giftCard['fromEmail']      = wc_clean( $giftInformation['wooposgc_email_from'] );
        }
        if ( isset( $giftInformation['wooposgc_amount'] ) ) {
            $giftCard['amount']         = wc_clean( $giftInformation['wooposgc_amount'] );

            if ( ! isset( $giftInformation['wooposgc_balance'] ) ) {
                $giftCard['balance']    = wc_clean( $giftInformation['wooposgc_amount'] );
                $giftCard['sendTheEmail'] = 1;
            }
        }
        if ( isset( $giftInformation['wooposgc_balance'] ) ) {
            $giftCard['balance']   = wc_clean( $giftInformation['wooposgc_balance'] );

        }
        if ( isset( $giftInformation['wooposgc_note'] ) ) {
            $giftCard['note']   = wc_clean( $giftInformation['wooposgc_note'] );

        }
        if ( isset( $giftInformation['wooposgc_expiry_date'] ) ) {
            $giftCard['expiry_date'] = wc_clean( $giftInformation['wooposgc_expiry_date'] );

        } else {
            $giftCard['expiry_date'] = '';
        }

        if ( ( $_POST['post_title'] == '' ) || isset( $giftInformation['wooposgc_regen_number'] ) ){

            if ( isset( $giftInformation['wooposgc_regen_number'] ) ) {

                if ( ( $giftInformation['wooposgc_regen_number'] == 'yes' ) ) {
                    $newNumber = apply_filters( 'wooposgc_regen_number', $this->generateNumber());

                    $wpdb->update( $wpdb->posts, array( 'post_title' => $newNumber ), array( 'ID' => ( int ) $_POST['ID'] ) );
                    $wpdb->update( $wpdb->posts, array( 'post_name' => $newNumber ), array( 'ID' => ( int ) $_POST['ID'] ) );
                }
            }
        }

        if( isset( $giftInformation['wooposgc_resend_email'] ) ) {
            $email = new WOOPOSGC_Giftcard_Email();
            $post = get_post( intval( $_POST['ID'] ) );
            //$email->sendEmail ( $post );


            $giftCard['sendTheEmail'] = 1;
        }

        update_post_meta( intval( $_POST['ID'] ), '_wooposgc_giftcard', $giftCard );

    }

    // Function to create the gift card
    public function sendCard( $giftInformation ) {


    }

    public static function reload_card( $order_id ) {
        global $wpdb, $current_user;

        $order = new WC_Order( $order_id );
        $theItems = $order->get_items();

        $numberofGiftCards = 0;

        $wooposgc_reload_card_check = ( get_option( 'wooposgc_giftcard_reload_card' ) <> NULL ? get_option( 'wooposgc_giftcard_reload_card' ) : __('Reload Card', 'wooposgc' )  );

        foreach( $theItems as $item ){
            $qty = (int) $item["quantity"];

            $theItem = (int) $item["product_id"];

            $is_giftcard = get_post_meta( $theItem, '_wooposgc_isgiftcard', true );
            if ( $is_giftcard != "yes" ) $is_giftcard = get_post_meta( $theItem, 'wooposgc_giftcard', true );

            if ( $is_giftcard == "yes" ) {

                for ($i = 0; $i < $qty; $i++){
                    if ( isset( $item["item_meta"][$wooposgc_reload_card_check] ) != '' ) {
                        if( ( $item["item_meta"][$wooposgc_reload_card_check] <> "NA") || ( $item["item_meta"][$wooposgc_reload_card_check] <> "") ) {
                            $giftCardInfo[$numberofGiftCards]["Reload"] = $item["item_meta"][$wooposgc_reload_card_check];
                        }

                        $giftCardTotal = (float) $item["line_subtotal"];
                        $giftCardInfo[$numberofGiftCards]["Amount"] = $giftCardTotal / $qty;

                        $numberofGiftCards++;
                    }
                }
            }
        }

        $giftNumbers = array();

        $giftcard = new WOOPOSGC_Giftcard();
        for ($i = 0; $i < $numberofGiftCards; $i++){
            if ( isset( $giftCardInfo[$i]['Reload'] ) ) {
                $giftCardID = wooposgc_get_giftcard_by_code( wc_clean( $giftCardInfo[$i]['Reload'] ) );
                $giftcard->wooposgc_increase_balance( $giftCardID, $giftCardInfo[$i]['Amount'] );

                $reloads = get_post_meta( $giftCardID, '_wooposgc_card_reloads', true );

                $giftCardInfo[$i]['Order'] = $order_id;

                $reloads[] = $giftCardInfo[$i];

                update_post_meta( $giftCardID, '_wooposgc_card_reloads', $reloads );
            }
        }


    }

    // Function to generate the gift card number for the card
    public function generateNumber( ){

        $randomNumber = substr( number_format( time() * rand(), 0, '', '' ), 0, 15 );

        return apply_filters('wooposgc_generate_number', $randomNumber);

    }

    // Function to check if a product is a gift card
    public static function wooposgc_is_giftcard( $giftcard_id ) {

        $is_giftcard = get_post_meta( $giftcard_id, '_wooposgc_isgiftcard', true );
        if ( $is_giftcard != "yes" ) $is_giftcard = get_post_meta( $giftcard_id, 'wooposgc_giftcard', true );

        if ( $is_giftcard != 'yes' ) {
            return false;
        }

        return true;

    }


    public static function wooposgc_get_giftcard_by_code( $value = '' ) {
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

    public function wooposgc_get_payment_amount( ){
        $giftcards      = WC()->session->giftcard_post;
        $cart           = WC()->session->cart;

        if ( isset( $giftcards ) ) {

            $balance = 0;

            foreach ($giftcards as $key => $card_id) {
                $balance += wooposgc_get_giftcard_balance( $card_id );
            }

            $charge_shipping    = get_option('wooposgc_enable_giftcard_charge_shipping');
            $charge_tax         = get_option('wooposgc_enable_giftcard_charge_tax');
            $charge_fee         = get_option('wooposgc_enable_giftcard_charge_fee');
            $charge_gifts       = get_option('wooposgc_enable_giftcard_charge_giftcard');

            $exclude_product    = array();
            $exclude_product    = array_filter( array_map( 'absint', explode( ',', get_option( 'wooposgc_giftcard_exclude_product_ids' ) ) ) );

            $giftcardPayment = 0;

            foreach( $cart as $key => $product ) {
                if ( isset( $product['product_id'] ) ) {
                    if( ! in_array( $product['product_id'], $exclude_product ) ) {

                        if ( ! WOOPOSGC_Giftcard::wooposgc_is_giftcard( $product['product_id'] ) ) {
                            if( $charge_tax == 'yes' ){
                                $giftcardPayment += $product['line_total'];
                                $giftcardPayment += $product['line_tax'];
                            } else {
                                $giftcardPayment += $product['line_total'];
                            }
                        } else {
                            if ( $charge_gifts == "yes" ) {
                                $giftcardPayment += $product['line_total'];
                            }
                        }
                    }
                }

            }

            if( $charge_shipping == 'yes' ) {
                $giftcardPayment += WC()->cart->shipping_total;
            }

            if( $charge_tax == "yes" ) {
                if( $charge_shipping == 'yes' ) {
                    $giftcardPayment += WC()->cart->shipping_tax_total;
                }
            }

            if( $charge_fee == "yes" ) {
                $giftcardPayment += WC()->cart->fee_total;
            }

            if( $charge_gifts == "yes" ) {
                $giftcardPayment += WC()->cart->fee_total;
            }



            if ( $giftcardPayment <= $balance ) {
                $display = $giftcardPayment;
            } else {
                $display = $balance;
            }
            return $display;
        }

    }


    public function wooposgc_decrease_balance( $giftCard_id ) {

        $payment = $this->wooposgc_get_payment_amount();
		$payment = apply_filters( 'wooposgc_convert_current_to_default', $payment );

        if ( $payment > wooposgc_get_giftcard_balance( $giftCard_id, true ) ) {
            $newBalance = 0;
        } else {
            $newBalance = wooposgc_get_giftcard_balance( $giftCard_id, true ) - $payment;
        }		

        wooposgc_set_giftcard_balance( $giftCard_id, $newBalance );

        // Check if the gift card ballance is 0 and if it is change the post status to zerobalance
        if( wooposgc_get_giftcard_balance( $giftCard_id ) == 0 ) {
            wooposgc_update_giftcard_status( $giftCard_id, 'zerobalance' );
        }



    }

    public function wooposgc_increase_balance( $giftCard_id, $amount ) {

        $newBalance = wooposgc_get_giftcard_balance( $giftCard_id ) + $amount;

        wooposgc_set_giftcard_balance( $giftCard_id, $newBalance );
    }


    public static function wooposgc_discount_total( $gift ) {

        //print_r( WC()->session->giftcard_post );

        $giftcard = new WOOPOSGC_Giftcard(  );

        $discount = $giftcard->wooposgc_get_payment_amount();
        //print_r( $discount );
        $gift -= round( $discount, 2 );

        //WC()->cart->discount_cart = $discount + WC()->cart->discount_cart;

        return $gift;
    }



}