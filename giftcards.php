<?php
/**
 * Plugin Name: WooPOS Gift Cards for WooCommerce
 * Description: WooPOS Gift Cards for WooCommerce allows your customers to purchase and redeem gift cards in both online store and physical stores.
 * Author: woopos
 * Author URI: https://woopos.com
 * Version: 2.5
 * License: GPL2
 *
 * Text Domain:     wooposgc
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

class Wooposgc_Giftcard_Object {
	protected $id = 0;
	protected $giftcard_number = '';
	protected $giftcard_description = '';
	protected $to = '';
	protected $to_email = '';
	protected $from = '';
	protected $from_email = '';

	protected $giftcard_amount = '';
	protected $giftcard_balance = '';
	protected $giftcard_note = '';
	protected $expiry_date = '';

	protected $status;
	protected $date_created;
	protected $date_created_gmt;
	protected $date_modified;
	protected $date_modified_gmt;
	protected $send_gift_card_email = '';
	protected $regenerate_card_number = '';

	public function __call($val, $args) {
		$get_or_set = substr($val, 0, 3);
		if($get_or_set == 'get' || $get_or_set == 'set') {
			$varname = strtolower(substr($val, 4));
		} else {
			throw new Exception('Bad method :'.$val, 500);
		}
		if ($get_or_set == 'get') {
			if (property_exists($this, $varname)) {
				return $this->$varname;
			} else {
				throw new Exception('Get Property does not exist: '.$varname, 500);
			}
		} elseif ($get_or_set == 'set') {
			if (property_exists($this, $varname)) {

				if('expiry_date' == $varname) {
					$var_val = date('Y-m-d', strtotime($args[0]));
				} else {
					$var_val = $args[0];
				}
				$this->{$varname} = $args[0];
			} else {
				throw new Exception('Set Property does not exist: '.$varname, 500);
			}
		}
	}

	public function delete() {
		wp_delete_post( $this->get_id() );
	}

	public function save() {
		$GLOBALS['wooposgc_giftcard_rest_make'] = true;
		$giftcard_number = $this->get_giftcard_number();
		$data = array(
					'post_title' => $giftcard_number,
					'post_author' => get_current_user_id(),
					'post_status' =>  'publish',
					'post_type' => 'wooposgc_giftcard',
					'post_modified' => current_time( 'Y-m-d H:i:s', $gmt = 0 ),
					'post_modified_gmt' => current_time( 'Y-m-d H:i:s', $gmt = 1 ),
		);

		$meta_data = array(
						'description' => $this->get_giftcard_description(),
						'to' => $this->get_to(),
						'toEmail' => $this->get_to_email(),
						'from' => $this->get_from(),
						'fromEmail' => $this->get_from_email(),
						'amount' => $this->get_giftcard_amount(),
						'balance' => $this->get_giftcard_balance(),
						'note' => $this->get_giftcard_note(),
						'expiry_date' => $this->get_expiry_date(),
						'sendTheEmail' => $this->get_send_gift_card_email(),
					);

		//error_log('data'.print_r($meta_data, true));
		if (0 == $this->get_id()) {
			$data['post_date'] = current_time( 'Y-m-d H:i:s', $gmt = 0 );
			$data['post_date_gmt'] = current_time( 'Y-m-d H:i:s', $gmt = 1 );

			$id = wp_insert_post($data, true);
			$this->set_id( $id );
		} else {
			$data['ID'] = $this->get_id();
			wp_update_post($data, true);
		}
		$post_id = $this->get_id();
		update_post_meta( $post_id, '_wooposgc_giftcard', $meta_data );
		do_action('wooposgc_woocommerce_after_save', $post_id);
	}
}

require_once(__DIR__.'/includes/class-wooposgc-api.php');
if( !class_exists( 'Wooposgc_Giftcards' ) ) {

    /**
     * Main Wooposgc_Giftcards class
     *
     * @since       1.0.0
     */
    class Wooposgc_Giftcards {

        /**
         * @var         Wooposgc_Giftcards $instance The one true Wooposgc_Giftcards
         * @since       1.0.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true Wooposgc_Giftcards
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new Wooposgc_Giftcards();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {

            define( 'WOOPOSGC_VERSION',   '1.0' ); // Plugin version
            define( 'WOOPOSGC_DIR',       plugin_dir_path( __FILE__ ) ); // Plugin Folder Path
            define( 'WOOPOSGC_URL',       plugins_url( 'woopos-gift-cards-for-woocommerce', 'giftcards.php' ) ); // Plugin Folder URL
            define( 'WOOPOSGC_FILE',      plugin_basename( __FILE__ )  ); // Plugin Root File


			// Plugin Folder Path
			if ( ! defined( 'WOOPOSGC_PATH' ) ) define( 'WOOPOSGC_PATH', plugin_dir_path( __FILE__ ) );


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
            require_once WOOPOSGC_DIR . 'includes/scripts.php';
            require_once WOOPOSGC_DIR . 'includes/functions.php';
            require_once WOOPOSGC_DIR . 'includes/post-type.php';

            require_once WOOPOSGC_DIR . 'includes/admin/metabox.php';

            if( ! class_exists( 'WOOPOSGC_Giftcard' ) ) {
                require_once WOOPOSGC_DIR . 'includes/class.giftcard.php';
            }

            if( ! class_exists( 'WOOPOSGC_Giftcard_Email' ) ) {
                require_once WOOPOSGC_DIR . 'includes/class.giftcardemail.php';
            }

            require_once WOOPOSGC_DIR . 'includes/giftcard-product.php';
            require_once WOOPOSGC_DIR . 'includes/giftcard-checkout.php';
            require_once WOOPOSGC_DIR . 'includes/giftcard-paypal.php';
            require_once WOOPOSGC_DIR . 'includes/giftcard-meta.php';
            require_once WOOPOSGC_DIR . 'includes/shortcodes.php';
            // require_once WOOPOSGC_DIR . 'includes/widgets.php';

            // advance feature
            require_once WOOPOSGC_DIR . 'admin/giftcardpro-settings.php';

            // Include scripts
            if( ! class_exists( 'WOOPOSGC_Giftcard' ) ) {
                require_once $WOOPOSGC_DIR . 'class.giftcard.php';
            }

            if( ! class_exists( 'WOOPOSGC_Giftcard_Email' ) ) {
                require_once $WOOPOSGC_DIR . 'class.giftcardemail.php';
            }


            // Include scripts
            require_once WOOPOSGC_DIR . 'includes/selector.php';
            require_once WOOPOSGC_DIR . 'includes/features/auto.php';
            require_once WOOPOSGC_DIR . 'includes/features/number.php';
            require_once WOOPOSGC_DIR . 'includes/features/price.php';
            require_once WOOPOSGC_DIR . 'includes/features/import.php';
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
            // Register settings
            $wooposgc_woo_giftcard_settings = get_option( 'wooposgc_wg_options' );

            add_filter( 'woocommerce_get_settings_pages', array( $this, 'wooposgc_add_settings_page'), 10, 1);
            add_filter( 'woocommerce_calculated_total', array( 'WOOPOSGC_Giftcard', 'wooposgc_discount_total'), 10, 2 );
            add_filter( 'plugin_action_links_' . WOOPOSGC_FILE, array( __CLASS__, 'plugin_action_links' ) );

            add_action( 'woocommerce_checkout_order_processed', array( 'WOOPOSGC_Giftcard', 'reload_card'), 10, 1);

            // Advanced feature
            add_action( 'plugins_loaded', array( $this, 'wooposgc_add_features' ), 15 );
			
			// woocommerce currency switcher
			add_action( 'plugins_loaded', array( $this, 'add_WOOCS_support' ), 15 );
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            load_plugin_textdomain( 'wooposgc', false, basename( dirname( __FILE__ ) ) . '/languages' );
        }

        public function wooposgc_add_settings_page( $settings ) {

            require_once WOOPOSGC_DIR . 'includes/class.settings.php';

            $settings[] = new WOOPOSGC_Settings();

            return apply_filters( 'wooposgc_setting_classes', $settings );
        }

        /**
         * Show action links on the plugin screen.
         *
         * @param   mixed $links Plugin Action links
         * @since       2.2.2
         * @return  array
         */
        public static function plugin_action_links( $links ) {
            $action_links = array(
                'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=wooposgc-giftcard' ) . '" title="' . esc_attr( __( 'View Gift Card Settings', 'wooposgc', 'woopos-gift-cards-for-woocommerce' ) ) . '">' . __( 'Settings', 'wooposgc', 'woopos-gift-cards-for-woocommerce' ) . '</a>',
            );

            return array_merge( $action_links, $links );
        }

        public function wooposgc_add_features () {

            $WOOPOSGC_GP["auto"] 	= WOOPOSGC_Auto_Send::getInstance();

            $WOOPOSGC_GP["cNumber"] 	= WOOPOSGC_Custom_Number::getInstance();

            $WOOPOSGC_GP["cPrice"] 	= WOOPOSGC_Custom_Price::getInstance();

            $WOOPOSGC_GP["import"]	= WOOPOSGC_CSV_Importer::getInstance();

            //$WOOPOSGC_GP["isc"] 		= WOOPOSGC_ISC::getInstance();
        }
		
		public function add_WOOCS_support(){
			if( class_exists('WOOCS') && isset($GLOBALS['WOOCS']) ){
				$WOOCSdecorator = new WOOCSClassDecorator($GLOBALS['WOOCS']);
				add_filter( 'wooposgc_get_giftcard_balance', array( $WOOCSdecorator, 'raw_woocommerce_price' ), 10, 1 );
				add_filter( 'wooposgc_convert_current_to_default', array( $WOOCSdecorator, 'convert_current_to_default' ), 10, 1 );
			}
		}

    }


} // End if class_exists check

class WOOCSClassDecorator{
	protected $_instance;

	public function convert_current_to_default($price){
		if (isset($_REQUEST['woocs_raw_woocommerce_price_currency'])) {
			$this->current_currency = $_REQUEST['woocs_raw_woocommerce_price_currency'];
		}
		$currencies = $this->get_currencies();

		if (in_array($this->current_currency, $this->no_cents)/* OR $currencies[$this->current_currency]['hide_cents'] == 1 */) {
			$precision = 0;
		} else {
			if ($this->current_currency != $this->default_currency) {
				$precision = $this->get_currency_price_num_decimals($this->current_currency, $this->price_num_decimals);
			} else {
				$precision = $this->get_currency_price_num_decimals($this->default_currency, $this->price_num_decimals);
			}
		}
		
		if ($this->current_currency != $this->default_currency) {
			if (isset($currencies[$this->current_currency]) AND $currencies[$this->current_currency] != NULL) {
				$price = number_format(floatval((float) $price / (float) $currencies[$this->current_currency]['rate']), $precision, $this->decimal_sep, '');
			}
		}
		
		return $price;
	}

	public function __construct($instance) {
		$this->_instance = $instance;
	}

	public function __call($method, $args) {
		return call_user_func_array(array($this->_instance, $method), $args);
	}

	public function __get($key) {
		return $this->_instance->$key;
	}

	public function __set($key, $val) {
		return $this->_instance->$key = $val;
	}
}

/**
 * The main function responsible for returning the one true Wooposgc_Giftcards
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \Wooposgc_Giftcards The one true Wooposgc_Giftcards
 *
 */
function Wooposgc_Giftcards_load() {
    if( ! class_exists( 'WooCommerce' ) ) {
        if( ! class_exists( 'WOOPOSGC_Giftcard_Activation' ) ) {
            require_once 'includes/class.activation.php';
        }
		$activation = new WOOPOSGC_Giftcard_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
        $activation = $activation->run();

        // return Wooposgc_Giftcards::instance();
    } else {
        return Wooposgc_Giftcards::instance();
    }

}
add_action( 'plugins_loaded', 'Wooposgc_Giftcards_load' );


/**
 * The activation hook is called outside of the singleton because WordPress doesn't
 * register the call from within the class, since we are preferring the plugins_loaded
 * hook for compatibility, we also can't reference a function inside the plugin class
 * for the activation function. If you need an activation function, put it here.
 *
 * @since       1.0.0
 * @return      void
 */
function wooposgc_giftcard_activation() {
    /* Activation functions here */
}
register_activation_hook( __FILE__, 'wooposgc_giftcard_activation' );
