<?php
/**
 * REST API Gitftcards controller
 *
 * Handles requests to the /giftcards endpoint.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Orders controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_CRUD_Controller
 */
class WC_REST_WOOPOS_Giftcards_Controller extends WC_REST_CRUD_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v2';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'woopos_giftcards';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'wooposgc_giftcard';

	/**
	 * If object is hierarchical.
	 *
	 * @var bool
	 */
	protected $hierarchical = true;

	/**
	 * Stores the request.
	 * @var array
	 */
	protected $request = array();

	/**
	 * Register the routes for orders.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			'args' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'wooposgc' ),
					'type'        => 'integer',
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(
					'force' => array(
						'default'     => false,
						'type'        => 'boolean',
						'description' => __( 'Whether to bypass trash and force deletion.', 'wooposgc' ),
					),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/batch', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'batch_items' ),
				'permission_callback' => array( $this, 'batch_items_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			'schema' => array( $this, 'get_public_batch_schema' ),
		) );
	}

	/**
	 * Get object.
	 *
	 * @since  3.0.0
	 * @param  int $id Object ID.
	 * @return WC_Data
	 */
	protected function get_object( $id ) {
		if (is_array($id)) {
			$data = $id;
		} else {
			$data = get_post($id);
			if ( is_null($data) ) {
				return false;
			}
		}

		$id = $data->ID;
		$obj = new Wooposgc_Giftcard_Object();
		$obj->set_id($data->ID);
		$obj->set_giftcard_number($data->post_title);

		$meta_data = get_post_meta($id, '_wooposgc_giftcard', true);
		$obj->set_giftcard_description( !empty($meta_data['description']) ? $meta_data['description'] : '' );
		$obj->set_to( !empty($meta_data['to']) ? $meta_data['to'] : '' );
        $obj->set_to_email( !empty($meta_data['toEmail']) ? $meta_data['toEmail'] : '' );
        $obj->set_from( !empty($meta_data['from']) ? $meta_data['from'] : '' );
        $obj->set_from_email( !empty($meta_data['fromEmail']) ? $meta_data['fromEmail'] : '' );

		$obj->set_giftcard_amount( !empty($meta_data['amount']) ? number_format($meta_data['amount'], 2) : '' );
		$obj->set_giftcard_balance( !empty($meta_data['balance']) ? number_format($meta_data['balance'], 2) : '' );
		$obj->set_giftcard_note( !empty($meta_data['note']) ? $meta_data['note'] : '' );
		$obj->set_expiry_date( !empty($meta_data['expiry_date']) ? date('Y-m-d', strtotime($meta_data['expiry_date'])) : '' );

        $obj->set_status( $data->post_status );
        $obj->set_date_created( $data->post_date );
        $obj->set_date_created_gmt( $data->post_date_gmt );
        $obj->set_date_modified( $data->post_modified );
        $obj->set_date_modified_gmt( $data->post_modified_gmt );

		/*
        $obj->set_send_gift_card_email('');
        $obj->set_regenerate_card_number('');
		*/
		return $obj;
		// Wooposgc_Giftcard_Object
		/*
		return wc_get_order( $id );
		*/
	}

	/**
	 * Expands an order item to get its data.
	 * @param WC_Order_item $item
	 * @return array
	 */
	protected function get_order_item_data( $item ) {
		$data           = $item->get_data();
		$format_decimal = array( 'subtotal', 'subtotal_tax', 'total', 'total_tax', 'tax_total', 'shipping_tax_total' );

		// Format decimal values.
		foreach ( $format_decimal as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$data[ $key ] = wc_format_decimal( $data[ $key ], $this->request['dp'] );
			}
		}

		// Add SKU and PRICE to products.
		if ( is_callable( array( $item, 'get_product' ) ) ) {
			$data['sku']   = $item->get_product() ? $item->get_product()->get_sku(): null;
			$data['price'] = $item->get_quantity() ? $item->get_total() / $item->get_quantity() : 0;
		}

		// Format taxes.
		if ( ! empty( $data['taxes']['total'] ) ) {
			$taxes = array();

			foreach ( $data['taxes']['total'] as $tax_rate_id => $tax ) {
				$taxes[] = array(
					'id'       => $tax_rate_id,
					'total'    => $tax,
					'subtotal' => isset( $data['taxes']['subtotal'][ $tax_rate_id ] ) ? $data['taxes']['subtotal'][ $tax_rate_id ] : '',
				);
			}
			$data['taxes'] = $taxes;
		} elseif ( isset( $data['taxes'] ) ) {
			$data['taxes'] = array();
		}

		// Remove names for coupons, taxes and shipping.
		if ( isset( $data['code'] ) || isset( $data['rate_code'] ) || isset( $data['method_title'] ) ) {
			unset( $data['name'] );
		}

		// Remove props we don't want to expose.
		unset( $data['order_id'] );
		unset( $data['type'] );

		return $data;
	}


	/**
	 * Prepare a single order output for response.
	 *
	 * @since  3.0.0
	 * @param  WC_Data         $object  Object data.
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_object_for_response( $object, $request ) {
		$this->request       = $request;
		$this->request['dp'] = is_null( $this->request['dp'] ) ? wc_get_price_decimals() : absint( $this->request['dp'] );
		$data                = $this->get_formatted_item_data( $object );
		$context             = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data                = $this->add_additional_fields_to_object( $data, $request );
		$data                = $this->filter_response_by_context( $data, $context );
		$response            = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $object, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type,
		 * refers to object type being prepared for the response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WC_Data          $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "woocommerce_rest_prepare_{$this->post_type}_object", $response, $object, $request );
	}

	/**
	 * Get formatted item data.
	 *
	 * @since  3.0.0
	 * @param  WC_Data $object WC_Data instance.
	 * @return array
	 */
	protected function get_formatted_item_data( $object ) {
		$ret_data = array();
		/*
		$keys  = array(
						'id',
						'giftcard_number',
						'giftcard_description',
						'to',
						'to_email',
						'from',
						'from_email',
						'status',
						'date_created',
						'date_created_gmt',
						'date_modified',
						'date_modified_gmt',
				);
		*/

		$ret_data['id'] = $object->get_id();
		$ret_data['giftcard_number'] = $object->get_giftcard_number();
		$ret_data['giftcard_description'] = $object->get_giftcard_description();
		$ret_data['to'] = $object->get_to();
		$ret_data['to_email'] = $object->get_to_email();
		$ret_data['from'] = $object->get_from();
		$ret_data['from_email'] = $object->get_from_email();

		$ret_data['giftcard_amount'] = $object->get_giftcard_amount();
		$ret_data['giftcard_balance'] = $object->get_giftcard_balance();
		$ret_data['giftcard_note'] = $object->get_giftcard_note();
		$exp_date = $object->get_expiry_date();
		if (empty($exp_date)) {
			$exp_date = __('Never Expire', 'wooposgc');
		}
		$ret_data['expiry_date'] = $exp_date;

		$ret_data['status'] = $object->get_status();
		$ret_data['date_created'] = $object->get_date_created();
		$ret_data['date_created_gmt'] = $object->get_date_created_gmt();
		$ret_data['date_modified'] = $object->get_date_modified();
		$ret_data['date_modified_gmt'] = $object->get_date_modified_gmt();

		return $ret_data;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WC_Data         $object  Object data.
	 * @param WP_REST_Request $request Request object.
	 * @return array                   Links for the given post.
	 */
	protected function prepare_links( $object, $request ) {
		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		// Temp comment Prashant
		/*
		if ( 0 !== (int) $object->get_customer_id() ) {
			$links['customer'] = array(
				'href' => rest_url( sprintf( '/%s/customers/%d', $this->namespace, $object->get_customer_id() ) ),
			);
		}

		if ( 0 !== (int) $object->get_parent_id() ) {
			$links['up'] = array(
				'href' => rest_url( sprintf( '/%s/orders/%d', $this->namespace, $object->get_parent_id() ) ),
			);
		}
		*/

		return $links;
	}

	/**
	 * Prepare objects query.
	 *
	 * @since  3.0.0
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		global $wpdb;

		$args = parent::prepare_objects_query( $request );

		// Set post_status.
		if ( 'any' !== $request['status'] ) {
			$args['post_status'] = 'wc-' . $request['status'];
		} else {
			$args['post_status'] = 'any';
		}

		if ( isset( $request['customer'] ) ) {
			if ( ! empty( $args['meta_query'] ) ) {
				$args['meta_query'] = array();
			}

			$args['meta_query'][] = array(
				'key'   => '_customer_user',
				'value' => $request['customer'],
				'type'  => 'NUMERIC',
			);
		}
		if ( isset( $request['giftcardnumber'] ) ) {
			$args['title'] = $request['giftcardnumber'];
		}
		if ( isset( $request['modified_after'] ) ) {
			$args['date_query'][0]['after'] = $request['modified_after'];
            $args['date_query'][0]['column'] = 'post_modified_gmt';
		}
		// Search by product.
		if ( ! empty( $request['product'] ) ) {
			$order_ids = $wpdb->get_col( $wpdb->prepare( "
				SELECT order_id
				FROM {$wpdb->prefix}woocommerce_order_items
				WHERE order_item_id IN ( SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_product_id' AND meta_value = %d )
				AND order_item_type = 'line_item'
			 ", $request['product'] ) );

			// Force WP_Query return empty if don't found any order.
			$order_ids = ! empty( $order_ids ) ? $order_ids : array( 0 );

			$args['post__in'] = $order_ids;
		}

		// Search.
		if ( ! empty( $args['s'] ) ) {
			$order_ids = wc_order_search( $args['s'] );

			if ( ! empty( $order_ids ) ) {
				unset( $args['s'] );
				$args['post__in'] = array_merge( $order_ids, array( 0 ) );
			}
		}

		return $args;
	}

	/**
	 * Only return writable props from schema.
	 *
	 * @param  array $schema
	 * @return bool
	 */
	protected function filter_writable_props( $schema ) {
		return empty( $schema['readonly'] );
	}

	/**
     * Prepare a single order for create or update.
     *
     * @param  WP_REST_Request $request Request object.
	 * @param  bool            $creating If is creating a new object.
	 * @return WP_Error|WC_Data
	 */
	protected function prepare_object_for_database( $request, $creating = false ) {
		$id        = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		$object     = new Wooposgc_Giftcard_Object();
		$object->set_id($id);
        if( 0 !== (int)$id)
            $object = $this->get_object( $id );
		$schema    = $this->get_item_schema();
		$data_keys = array_keys( array_filter( $schema['properties'], array( $this, 'filter_writable_props' ) ) );


		// Handle all writable props.
		foreach ( $data_keys as $key ) {
			$value = $request[ $key ];

			if ( ! is_null( $value ) ) {
				switch ( $key ) {

					default :
						if ( is_callable( array( $object, "set_{$key}" ) ) ) {
							if(	'expiry_date' == $key ) {
								$value = date('Y-m-d', strtotime($value));
							}
							$object->{"set_{$key}"}( $value );
						}
						break;
				}
			}
		}
		return apply_filters( "woocommerce_rest_pre_insert_{$this->post_type}_object", $object, $request, $creating );
    }

	/**
	 * Save an object data.
	 *
	 * @since  3.0.0
	 * @param  WP_REST_Request $request  Full details about the request.
	 * @param  bool            $creating If is creating a new object.
	 * @return WC_Data|WP_Error
	 */
	protected function save_object( $request, $creating = false ) {
		try {
			$object = $this->prepare_object_for_database( $request, $creating );

			if ( is_wp_error( $object ) ) {
				return $object;
			}
			$object->save();
			return $this->get_object( $object->get_id() );
		} catch ( WC_Data_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Update address.
	 *
	 * @param WC_Order $order
	 * @param array $posted
	 * @param string $type
	 */
	protected function update_address( $order, $posted, $type = 'billing' ) {
		foreach ( $posted as $key => $value ) {
			if ( is_callable( array( $order, "set_{$type}_{$key}" ) ) ) {
				$order->{"set_{$type}_{$key}"}( $value );
			}
		}
	}

	/**
	 * Gets the product ID from the SKU or posted ID.
	 *
	 * @param array $posted Request data
	 *
	 * @return int
	 * @throws WC_REST_Exception
	 */
	protected function get_product_id( $posted ) {
		if ( ! empty( $posted['sku'] ) ) {
			$product_id = (int) wc_get_product_id_by_sku( $posted['sku'] );
		} elseif ( ! empty( $posted['product_id'] ) && empty( $posted['variation_id'] ) ) {
			$product_id = (int) $posted['product_id'];
		} elseif ( ! empty( $posted['variation_id'] ) ) {
			$product_id = (int) $posted['variation_id'];
		} else {
			throw new WC_REST_Exception( 'woocommerce_rest_required_product_reference', __( 'Product ID or SKU is required.', 'wooposgc' ), 400 );
		}
		return $product_id;
	}

	/**
	 * Maybe set an item prop if the value was posted.
	 *
	 * @param WC_Order_Item $item
	 * @param string $prop
	 * @param array $posted Request data.
	 */
	protected function maybe_set_item_prop( $item, $prop, $posted ) {
		if ( isset( $posted[ $prop ] ) ) {
			$item->{"set_$prop"}( $posted[ $prop ] );
		}
	}

	/**
	 * Maybe set item props if the values were posted.
	 *
	 * @param WC_Order_Item $item
	 * @param string[] $props
	 * @param array $posted Request data.
	 */
	protected function maybe_set_item_props( $item, $props, $posted ) {
		foreach ( $props as $prop ) {
			$this->maybe_set_item_prop( $item, $prop, $posted );
		}
	}

	/**
	 * Maybe set item meta if posted.
	 *
	 * @param WC_Order_Item $item
	 * @param array $posted Request data.
	 */
	protected function maybe_set_item_meta_data( $item, $posted ) {
		if ( ! empty( $posted['meta_data'] ) && is_array( $posted['meta_data'] ) ) {
			foreach ( $posted['meta_data'] as $meta ) {
				if ( isset( $meta['key'] ) ) {
					$value = isset( $meta['value'] ) ? $meta['value'] : null;
					$item->update_meta_data( $meta['key'], $value, isset( $meta['id'] ) ? $meta['id'] : '' );
				}
			}
		}
	}

	/**
	 * Create or update an order shipping method.
	 *
	 * @param array  $posted $shipping Item data.
	 * @param string $action 'create' to add shipping or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 * @return WC_Order_Item_Shipping
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_shipping_lines( $posted, $action = 'create', $item = null ) {
		$item = is_null( $item ) ? new WC_Order_Item_Shipping( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;

		if ( 'create' === $action ) {
			if ( empty( $posted['method_id'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_shipping_item', __( 'Shipping method ID is required.', 'wooposgc' ), 400 );
			}
		}

		$this->maybe_set_item_props( $item, array( 'method_id', 'method_title', 'total' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

		return $item;
	}

	/**
	 * Create or update an order coupon.
	 *
	 * @param array  $posted Item data.
	 * @param string $action 'create' to add coupon or 'update' to update it.
	 * @param object $item Passed when updating an item. Null during creation.
	 * @return WC_Order_Item_Coupon
	 * @throws WC_REST_Exception Invalid data, server error.
	 */
	protected function prepare_coupon_lines( $posted, $action = 'create', $item = null ) {
		$item = is_null( $item ) ? new WC_Order_Item_Coupon( ! empty( $posted['id'] ) ? $posted['id'] : '' ) : $item;

		if ( 'create' === $action ) {
			if ( empty( $posted['code'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_coupon_coupon', __( 'Coupon code is required.', 'wooposgc' ), 400 );
			}
		}

		$this->maybe_set_item_props( $item, array( 'code', 'discount' ), $posted );
		$this->maybe_set_item_meta_data( $item, $posted );

		return $item;
	}

	/**
	 * Wrapper method to create/update order items.
	 * When updating, the item ID provided is checked to ensure it is associated
	 * with the order.
	 *
	 * @param WC_Order $order order object.
	 * @param string   $item_type The item type.
	 * @param array    $posted item provided in the request body.
	 * @throws WC_REST_Exception If item ID is not associated with order.
	 */
	protected function set_item( $order, $item_type, $posted ) {
		global $wpdb;

		if ( ! empty( $posted['id'] ) ) {
			$action = 'update';
		} else {
			$action = 'create';
		}

		$method = 'prepare_' . $item_type;
		$item   = null;

		// Verify provided line item ID is associated with order.
		if ( 'update' === $action ) {
			$item = $order->get_item( absint( $posted['id'] ), false );

			if ( ! $item ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_item_id', __( 'Order item ID provided is not associated with order.', 'wooposgc' ), 400 );
			}
		}

		// Prepare item data.
		$item = $this->$method( $posted, $action, $item );

		do_action( 'woocommerce_rest_set_order_item', $item, $posted );

		// If creating the order, add the item to it.
		if ( 'create' === $action ) {
			$order->add_item( $item );
		} else {
			$item->save();
		}
	}

	/**
	 * Helper method to check if the resource ID associated with the provided item is null.
	 * Items can be deleted by setting the resource ID to null.
	 *
	 * @param array $item Item provided in the request body.
	 * @return bool True if the item resource ID is null, false otherwise.
	 */
	protected function item_is_null( $item ) {
		$keys = array( 'product_id', 'method_id', 'method_title', 'name', 'code' );

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $item ) && is_null( $item[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get order statuses without prefixes.
	 * @return array
	 */
	protected function get_order_statuses() {
		$order_statuses = array();

		foreach ( array_keys( wc_get_order_statuses() ) as $status ) {
			$order_statuses[] = str_replace( 'wc-', '', $status );
		}

		return $order_statuses;
	}

	/**
	 * Get the Order's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'wooposgc' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'giftcard_number' => array(
					'description' => __( 'Giftcard Number.', 'wooposgc' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'giftcard_description' => array(
					'description' => __( 'Gift Card description.', 'wooposgc' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'to' => array(
					'description' => __( 'To Who is getting this gift card.', 'wooposgc' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'to_email' => array(
					'description' => __( 'To Email.', 'wooposgc' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'from' => array(
					'description' => __( 'From', 'wooposgc' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'from_email' => array(
					'description' => __( 'Email From.', 'wooposgc' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'giftcard_amount' => array(
					'description' => __( 'Gift Card Amount', 'wooposgc' ),
					'type'        => 'float',
					'context'     => array( 'view', 'edit' ),
				),
				'giftcard_balance' => array(
					'description' => __( 'Gift Card Balance', 'wooposgc' ),
					'type'        => 'float',
					'context'     => array( 'view', 'edit' ),
				),
				'giftcard_note' => array(
					'description' => __( 'Gift Card Note', 'wooposgc' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'expiry_date' => array(
					'description' => __( "Expiry Date", 'wooposgc' ),
					'type'        => 'date',
					'context'     => array( 'view', 'edit' ),
				),
				'status' => array(
					'description' => __( 'Order status.', 'wooposgc' ),
					'type'        => 'string',
					'default'     => 'pending',
					'enum'        => $this->get_order_statuses(),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'date_created' => array(
					'description' => __( "The date the order was created, in the site's timezone.", 'wooposgc' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'date_created_gmt' => array(
					'description' => __( "The date the order was created, as GMT.", 'wooposgc' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'date_modified' => array(
					'description' => __( "The date the order was last modified, in the site's timezone.", 'wooposgc' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'date_modified_gmt' => array(
					'description' => __( "The date the order was last modified, as GMT.", 'wooposgc' ),
					'type'        => 'date-time',
					'context'     => array( 'view'),
					'readonly'    => true,
				),
				'send_gift_card_email' => array(
					'description' => __( 'Send Gift Card Email.', 'wooposgc' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'edit' ),
				),
				'regenerate_card_number' => array(
					'description' => __( 'Regenerate Card Number.', 'wooposgc' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['status'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to orders assigned a specific status.', 'wooposgc' ),
			'type'              => 'string',
			'enum'              => array_merge( array( 'any' ), $this->get_order_statuses() ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['dp'] = array(
			'default'           => wc_get_price_decimals(),
			'description'       => __( 'Number of decimal points to use in each resource.', 'wooposgc' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Get a collection of posts.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$query_args    = $this->prepare_objects_query( $request );
		$query_results = $this->get_objects( $query_args );

		$objects = array();
		foreach ( $query_results['objects'] as $object ) {
			if ( ! wc_rest_check_post_permissions( $this->post_type, 'read', $object->get_id() ) ) {
				continue;
			}

			$data = $this->prepare_object_for_response( $object, $request );
			$objects[] = $this->prepare_response_for_collection( $data );
		}

		$page      = (int) $query_args['paged'];
		$max_pages = $query_results['pages'];

		$response = rest_ensure_response( $objects );
		$response->header( 'X-WP-Total', $query_results['total'] );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ) );

		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Delete a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$force  = (bool) $request['force'];
		$object = $this->get_object( (int) $request['id'] );

		$result = false;

		if ( ! $object || 0 === $object->get_id() ) {
			return new WP_Error( "woocommerce_rest_{$this->post_type}_invalid_id", __( 'Invalid ID.', 'wooposgc' ), array( 'status' => 404 ) );
		}

		$supports_trash = EMPTY_TRASH_DAYS > 0 && is_callable( array( $object, 'get_status' ) );

		/**
		 * Filter whether an object is trashable.
		 *
		 * Return false to disable trash support for the object.
		 *
		 * @param boolean $supports_trash Whether the object type support trashing.
		 * @param WC_Data $object         The object being considered for trashing support.
		 */
		$supports_trash = apply_filters( "woocommerce_rest_{$this->post_type}_object_trashable", $supports_trash, $object );

		if ( ! wc_rest_check_post_permissions( $this->post_type, 'delete', $object->get_id() ) ) {
			/* translators: %s: post type */
			return new WP_Error( "woocommerce_rest_user_cannot_delete_{$this->post_type}", sprintf( __( 'Sorry, you are not allowed to delete %s.', 'wooposgc' ), $this->post_type ), array( 'status' => rest_authorization_required_code() ) );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_object_for_response( $object, $request );

		// If we're forcing, then delete permanently.
		if ( $force ) {
			$object->delete( true );
			$result = 0 === $object->get_id();
		} else {
			// If we don't support trashing for this type, error out.
			if ( ! $supports_trash ) {
				/* translators: %s: post type */
				return new WP_Error( 'woocommerce_rest_trash_not_supported', sprintf( __( 'The %s does not support trashing.', 'wooposgc' ), $this->post_type ), array( 'status' => 501 ) );
			}

			// Otherwise, only trash if we haven't already.
			if ( is_callable( array( $object, 'get_status' ) ) ) {
				if ( 'trash' === $object->get_status() ) {
					/* translators: %s: post type */
					return new WP_Error( 'woocommerce_rest_already_trashed', sprintf( __( 'The %s has already been deleted.', 'wooposgc' ), $this->post_type ), array( 'status' => 410 ) );
				}

				$object->delete();
				$result = 'trash' === $object->get_status();
			}
		}

		if ( ! $result ) {
			/* translators: %s: post type */
			return new WP_Error( 'woocommerce_rest_cannot_delete', sprintf( __( 'The %s cannot be deleted.', 'wooposgc' ), $this->post_type ), array( 'status' => 500 ) );
		}

		/**
		 * Fires after a single object is deleted or trashed via the REST API.
		 *
		 * @param WC_Data          $object   The deleted or trashed object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "woocommerce_rest_delete_{$this->post_type}_object", $object, $response, $request );

		return $response;
	}

	/**
	 * Create a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			/* translators: %s: post type */
			return new WP_Error( "woocommerce_rest_{$this->post_type}_exists", sprintf( __( 'Cannot create existing %s.', 'wooposgc' ), $this->post_type ), array( 'status' => 400 ) );
		}

		$object = $this->save_object( $request, true );

		if ( is_wp_error( $object ) ) {
			return $object;
		}

		try {
			$this->update_additional_fields_for_object( $object, $request );
		} catch ( WC_Data_Exception $e ) {
			$object->delete();
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		} catch ( WC_REST_Exception $e ) {
			$object->delete();
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		/**
		 * Fires after a single object is created or updated via the REST API.
		 *
		 * @param WC_Data         $object    Inserted object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating object, false when updating.
		 */
		do_action( "woocommerce_rest_insert_{$this->post_type}_object", $object, $request, true );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_object_for_response( $object, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->get_id() ) ) );

		return $response;
	}

}