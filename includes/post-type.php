<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

function wooposgc_create_post_type() {
    $show_in_menu = current_user_can( 'manage_woocommerce' ) ? 'woocommerce' : false;

    register_post_type( 'wooposgc_giftcard',
        array(
            'labels' => array(
                'name'                  => __( 'Gift Cards', 'wooposgc' ),
                'singular_name'         => __( 'Gift Card', 'wooposgc' ),
                'menu_name'             => _x( 'Gift Cards', 'Admin menu name', 'wooposgc' ),
                'add_new'               => __( 'Add Gift Card', 'wooposgc' ),
                'add_new_item'          => __( 'Add New Gift Card', 'wooposgc' ),
                'edit'                  => __( 'Edit', 'wooposgc' ),
                'edit_item'             => __( 'Edit Gift Card', 'wooposgc' ),
                'new_item'              => __( 'New Gift Card', 'wooposgc' ),
                'view'                  => __( 'View Gift Cards', 'wooposgc' ),
                'view_item'             => __( 'View Gift Card', 'wooposgc' ),
                'search_items'          => __( 'Search Gift Cards', 'wooposgc' ),
                'not_found'             => __( 'No Gift Cards found', 'wooposgc' ),
                'not_found_in_trash'    => __( 'No Gift Cards found in trash', 'wooposgc' ),
                'parent'                => __( 'Parent Gift Card', 'wooposgc' )
                ),

            'public'                => true,
            'has_archive'           => true,
            'publicly_queryable'    => false,
            'exclude_from_search'   => false,
            'show_in_menu'          => $show_in_menu,
            'hierarchical'          => false,
            'supports'              => array( 'title', 'comments' ),
			'show_in_rest' 			=> true,
			 'rest_base' 			=> 'wooposgc_giftcard',
        )
    );

    register_post_status( 'zerobalance', array(
        'label'                     => __( 'Zero Balance', 'wooposgc' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Zero Balance <span class="count">(%s)</span>', 'Zero Balance <span class="count">(%s)</span>', 'wooposgc' )
    ) );
    
}
add_action( 'init', 'wooposgc_create_post_type' );


/**
 * Define our custom columns shown in admin.
 * @param  string $column
 *
 */
function wooposgc_add_columns( $columns ) {
    $new_columns = ( is_array( $columns ) ) ? $columns : array();
    unset( $new_columns['title'] );
    unset( $new_columns['date'] );
    unset( $new_columns['comments'] );

    //all of your columns will be added before the actions column on the Giftcard page

    $new_columns["title"]       = __( 'Giftcard Number', 'wooposgc' );
    $new_columns["amount"]      = __( 'Giftcard Amount', 'wooposgc' );
    $new_columns["balance"]     = __( 'Remaining Balance', 'wooposgc' );
    $new_columns["buyer"]       = __( 'Buyer', 'wooposgc' );
    $new_columns["recipient"]   = __( 'Recipient', 'wooposgc' );
    $new_columns["expiry_date"] = __( 'Expiry date', 'wooposgc' );
    $new_columns["sentEmail"]   = __( 'Sent?', 'wooposgc' );

    $new_columns['comments']    = $columns['comments'];
    $new_columns['date']        = __( 'Creation Date', 'wooposgc' );

    return  apply_filters( 'wooposgc_giftcard_columns', $new_columns);
}
add_filter( 'manage_edit-wooposgc_giftcard_columns', 'wooposgc_add_columns' );



/**
 * Define our custom columns contents shown in admin.
 * @param  string $column
 *
 */
function wooposgc_custom_columns( $column ) {
    global $post;

    $giftcardInfo = get_post_meta( $post->ID, '_wooposgc_giftcard', true );


    switch ( $column ) {

        case "buyer" :
            echo '<div><strong>' . esc_html( isset( $giftcardInfo[ 'from' ] ) ? $giftcardInfo[ 'from' ] : '' ) . '</strong><br />';
            echo '<span style="font-size: 0.9em">' . esc_html( isset( $giftcardInfo[ 'fromEmail' ] ) ? $giftcardInfo[ 'fromEmail' ] : '' ) . '</div>';
            break;

        case "recipient" :
            echo '<div><strong>' . esc_html( isset( $giftcardInfo[ 'to' ] ) ? $giftcardInfo[ 'to' ] : '' ) . '</strong><br />';
            echo '<span style="font-size: 0.9em">' . esc_html( isset( $giftcardInfo[ 'toEmail' ] ) ? $giftcardInfo[ 'toEmail' ] : '' ) . '</span></div>';
        break;

        case "amount" :
            $price = isset( $giftcardInfo[ 'amount' ] ) ? $giftcardInfo[ 'amount' ] : '';
            echo wc_price( $price );
        break;

        case "balance" :
            $price = isset( $giftcardInfo[ 'balance' ] ) ? $giftcardInfo[ 'balance' ] : '';
            echo wc_price( $price );
        break;

        case "sentEmail" :
            $sent = isset( $giftcardInfo[ 'sendTheEmail' ] ) ? $giftcardInfo[ 'sendTheEmail' ] : '';
            if ( $sent == 1 ) {
                echo "Yes";
            } else {
                echo "No";
            }
        break;

        case "expiry_date" :
            $expiry_date = isset( $giftcardInfo[ 'expiry_date' ] ) ? $giftcardInfo[ 'expiry_date' ] : '';

            if ( $expiry_date )
                echo esc_html( date_i18n( 'F j, Y', strtotime( $expiry_date ) ) );
            else
                echo '&ndash;';
        break;
    }
}
add_action( 'manage_wooposgc_giftcard_posts_custom_column', 'wooposgc_custom_columns', 2 );



function wpfstop_change_default_title( $title ){

    $screen = get_current_screen();

    if ( 'wooposgc_giftcard' == $screen->post_type ){
        $title = __( 'Enter Gift Card Number Here', 'wooposgc' );
    }

    return $title;
}

add_filter( 'enter_title_here', 'wpfstop_change_default_title' );

