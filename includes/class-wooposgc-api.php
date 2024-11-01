<?php
/**
 * WooCommerce Subscriptions API
 *
 * Handles WC-API endpoint requests related to Subscriptions
 *
 * @author   Prospress
 * @since    2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WOOPOSGC_API {

	public static function init() {
		add_filter( 'woocommerce_api_classes', __CLASS__ . '::includes' );

		add_action( 'rest_api_init', __CLASS__ . '::register_routes', 15 );
	}

	/**
	 * Include the required files for the REST API and add register the giftcard
	 * API class in the WC_API_Server.
	 *
	 * @param Array $wc_api_classes WC_API::registered_resources list of api_classes
	 * @return array
	 */
	public static function includes( $wc_api_classes ) {

		if ( ! defined( 'WC_API_REQUEST_VERSION' ) || 3 == WC_API_REQUEST_VERSION ) {
			require_once( 'api/legacy/class-wc-api-giftcards.php' );
			array_push( $wc_api_classes, 'WC_API_Giftcards' );
		}
		return $wc_api_classes;
	}

	/**
	 * Load the new REST API Giftcard endpoints
	 *
	 * @since 2.1
	 */
	public static function register_routes() {
		global $wp_version;

		if ( version_compare( $wp_version, 4.4, '<' )) {
			return;
		}

		require_once( __DIR__.'/api/class-wc-rest-giftcardss-controller.php' );
		
		foreach ( array( 'WC_REST_WOOPOS_Giftcards_Controller' ) as $api_class ) {
			$controller = new $api_class();
			$controller->register_routes();
		}
	}

}

WOOPOSGC_API::init();
