<?php

/**
 * Register all actions and filters for the plugin
 *
 * @link       https://github.com/magicoli/multipass
 * @since      0.1.0
 *
 * @package    MultiPass
 * @subpackage MultiPass/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    MultiPass
 * @subpackage MultiPass/includes
 * @author     Your Name <email@example.com>
 */
class Mltp_WooCommerce extends Mltp_Modules {

	/**
	 * The array of actions registered with WordPress.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
	 */
	protected $actions;

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @since    0.1.0
	 * @access   protected
	 * @var      array    $filters    The filters registered with WordPress to fire when the plugin loads.
	 */
	protected $filters;

	/**
	 * Initialize the collections used to maintain the actions and filters.
	 *
	 * @since    0.1.0
	 */
	public function __construct() {
		register_activation_hook( MULTIPASS_FILE, __CLASS__ . '::activate' );
		// register_deactivation_hook( MULTIPASS_FILE, __CLASS__ . '::deactivate' );
	}

	/**
	 * Register the filters and actions with WordPress.
	 *
	 * @since    0.1.0
	 */
	public function run() {

		$this->actions = array(
			array(
				'hook'          => 'wp_insert_post',
				'callback'      => 'wp_insert_post_action',
				'accepted_args' => 3,
			),
			array(
				'hook'          => 'save_post_shop_order',
				'callback'      => 'save_post_shop_order_action',
				'accepted_args' => 3,
			),
		);

		$this->filters = array(
			array(
				'hook'     => 'rwmb_meta_boxes',
				'callback' => 'register_fields',
			),
			array(
				'hook'     => 'mb_settings_pages',
				'callback' => 'register_settings_pages',
			),
			array(
				'hook'     => 'rwmb_meta_boxes',
				'callback' => 'register_settings_fields',
			),
			array(
				'hook'     => 'multipass_register_terms_prestation-item-source',
				'callback' => 'register_sources_filter',
			),

			array(
				'hook'     => 'multipass_update_service_title',
				'callback' => 'update_service_title',
			),

			array(
				'hook'     => 'manage_edit-shop_order_columns',
				'callback' => 'add_shop_order_columns',
			),

			array(
				'hook'          => 'manage_prestation_posts_custom_column',
				'callback'      => 'prestations_columns_display',
				'accepted_args' => 2,
			),
			array(
				'hook'          => 'manage_shop_order_posts_custom_column',
				'callback'      => 'shop_orders_columns_display',
				'accepted_args' => 2,
			),

			array(
				'hook'          => 'woocommerce_order_data_store_cpt_get_orders_query',
				'callback'      => 'wc_get_orders_handle_prestation_id',
				'accepted_args' => 2,
			),

			// array(
			// 'hook' => 'multipass_managed_list',
			// 'component' => get_parent_class();
			// 'callback' => 'managed_list_filter',
			// )
		);

		$defaults = array(
			'component'     => __CLASS__,
			'priority'      => 10,
			'accepted_args' => 1,
		);

		foreach ( $this->filters as $hook ) {
			$hook = array_merge( $defaults, $hook );
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			$hook = array_merge( $defaults, $hook );
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

	}

	static function activate() {
		self::sync_orders();
	}

	static function register_fields( $meta_boxes ) {
		// WooCommerce settings

		$prefix = 'woocommerce_';

		$meta_boxes['multipass-settings']['fields']['currency_options'] = array(
			'name' => __( 'Currency Options', 'multipass' ),
			'id'   => $prefix . 'currency',
			'type' => 'custom_html',
			'std'  => sprintf(
				__( 'Set currency options in %1$sWooCommerce settings page%2$s', 'multipass' ),
				'<a href="' . get_admin_url( null, 'admin.php?page=wc-settings#pricing_options-description' ) . '">',
				'</a>',
			),
		);

		$meta_boxes['woocommerce_settings'] = array(
			'title'          => __( 'WooCommerce Settings', 'multipass' ),
			'id'             => 'multipass-woocommerce',
			'settings_pages' => array( 'multipass' ),
			'tab'            => 'woocommerce',
			'fields'         => array(
				array(
					'name'              => __( 'Synchronize now', 'multipass' ),
					'id'                => $prefix . 'sync_orders',
					'type'              => 'switch',
					'desc'              => __( 'Sync orders and prestations, create prestation if none exist. Only useful after plugin activation or if out of sync.', 'multipass' ),
					'style'             => 'rounded',
					'sanitize_callback' => 'Mltp_WooCommerce::sync_orders_validation',
					'save_field'        => false,
				),
			),
		);

		$wc_term    = get_term_by( 'slug', 'woocommerce', 'prestation-item-source' );
		$wc_term_id = ( $wc_term ) ? get_term_by( 'slug', 'woocommerce', 'prestation-item-source' )->term_id : 'woocommerce';
		// Order info on prestation-item

		// Prestation info on WC Orders
		$prefix       = 'prestation_';
		$meta_boxes[] = array(
			'title'      => __( 'Prestation', 'multipass' ),
			'id'         => 'prestation-woocommerce-order',
			'post_types' => array( 'shop_order' ),
			'context'    => 'side',
			'fields'     => array(
				array(
					// 'name'       => __('Prestation', 'multipass' ),
					'id'         => $prefix . 'id',
					'type'       => 'post',
					'post_type'  => array( 'prestation' ),
					'field_type' => 'select_advanced',
				),
				array(
					'id'       => $prefix . 'prestation_link',
					'type'     => 'custom_html',
					'callback' => __CLASS__ . '::display_prestation_link',
				),
			),
		);

		$meta_boxes['services']['fields'][] = array(
			'name'          => __( 'Product', 'multipass' ),
			'id'            => 'service_product_id',
			'type'          => 'post',
			'post_type'     => array( 'product' ),
			'field_type'    => 'select_advanced',
			'admin_columns' => array(
				'position'   => 'before date',
				'sort'       => true,
				'searchable' => true,
			),
			'columns'       => 3,
		);

		return $meta_boxes;
	}

	static function register_sources_filter( $sources ) {
		$sources['woocommerce'] = 'WooCommerce';
		return $sources;
	}

	static function wc_get_orders_handle_prestation_id( $query, $query_vars ) {
		if ( ! empty( $query_vars['prestation_id'] ) ) {
			$query['meta_query'][] = array(
				'key'   => 'prestation_id',
				'value' => esc_attr( $query_vars['prestation_id'] ),
			);
		}
		return $query;
	}

	static function display_prestation_link( $arg = null, $field = null ) {
		global $post;
		$prestation_id = get_post_meta( $post->ID, 'prestation_id', true );
		$link          = get_edit_post_link( $prestation_id );

		if ( ! empty( $link ) ) {
			echo sprintf(
				'<a href="%s">%s</a>',
				$link,
				__( 'View prestation', 'multipass' ),
			);
		}
	}

	static function register_settings_pages( $settings_pages ) {
		$settings_pages['multipass']['tabs']['woocommerce'] = 'WooCommerce';

		return $settings_pages;
	}

	static function register_settings_fields( $meta_boxes ) {
		$prefix = 'woocommerce_';

		$meta_boxes['multipass-woocommerce-settings'] = array(
			'title'          => __( 'WooCommerce Settings', 'multipass' ),
			'id'             => 'multipass-woocommerce-settings',
			'settings_pages' => array( 'multipass' ),
			'tab'            => 'woocommerce',
			'fields'         => array(),
		);

		return $meta_boxes;
	}

	/**
	 * Tweak to force add shop order columns, which should be added by mb_relationships_init but aren't
	 */
	static function add_shop_order_columns( $columns ) {
		foreach ( $columns as $key => $value ) {
			$updated_columns[ $key ] = $value;
			if ( $key == 'order_number' ) {
				$updated_columns['prestation_id'] = __( 'Prestation', 'multipass' );
			}
		}
		if ( isset( $updated_columns ) ) {
			$columns = $updated_columns;
		}
		return $columns;
	}

	static function get_related_links( $post_id, $relation_id, $direction ) {
		if ( empty( $post_id ) || empty( $relation_id ) ) {
			return array();
		}
		$related = array();

		return $related;
	}

	static function prestations_columns_display( $column, $post_id ) {
		switch ( $column ) {
			case 'wc-order-prestation_to':
				$related = self::get_related_links( $post_id, 'wc-order-prestation', 'from' );
				echo 'O ' . join( ' ', $related );
				break;
		}
	}

	static function shop_orders_columns_display( $column, $post_id ) {
		switch ( $column ) {
			case 'prestation_id':
				$prestation_id = get_post_meta( $post_id, 'prestation_id', true );
				if ( ! empty( $prestation_id ) ) {
					echo sprintf(
						'<a href="%s">#%s</a>',
						get_edit_post_link( $prestation_id ),
						get_post_field( 'post_name', $prestation_id ),
					);
				}
				break;
		}
	}

	function background_process() {
		$this->background_queue = new Mltp_WooCommerce_Process();

		// $action = __CLASS__ . '::fetch_mails';
		// if(get_transient('Mltp_WooCommerce_wait')) return;
		// set_transient('Mltp_WooCommerce_wait', true, 30);
		//
		// if(MultiPass::get_option('email_processing', false))
		// $this->background_queue->push_to_queue(__CLASS__ . '::fetch_mails');
		//
		// $this->background_queue->save()->dispatch();

		// One-off task:
		//
		// $this->background_request = new Mltp_WooCommerce_Request();
		// $this->background_request->data( array( 'value1' => $value1, 'value2' => $value2 ) );
		// $this->background_request->dispatch();
	}

	static function update_service_title( $data ) {
		if ( empty( $_REQUEST['service_product_id'] ) ) {
			return $data;
		}

		if ( empty( $data['post_title'] ) ) {
			$data['post_title'] = get_the_title( $_REQUEST['service_product_id'] );
			$data['post_name']  = sanitize_title( $data['post_title'] );
		}

		return $data;
	}


	static function save_post_shop_order_action( $post_id, $post, $update ) {
		if ( ! $update ) {
			return;
		}

		remove_action( current_action(), __CLASS__ . '::' . __FUNCTION__ );

		self::update_order_prestation( $post_id, $post, $update );

		add_action( current_action(), __CLASS__ . '::' . __FUNCTION__, 10, 3 );
	}

	static function wp_insert_post_action( $post_id, $post, $update ) {
		if ( ! $update ) {
			return;
		}
		if ( MultiPass::is_new_post() ) {
			return; // new posts are empty
		}

		remove_action( current_action(), __CLASS__ . '::' . __FUNCTION__ );
		switch ( $post->post_type ) {
			// case 'shop_order':
			// self::update_order_prestation($post_id, $post, $update );
			// break;

			case 'prestation':
				self::update_prestation_orders( $post_id, $post, $update );
				break;
		}
		add_action( current_action(), __CLASS__ . '::' . __FUNCTION__, 10, 3 );
	}

	static function update_prestation_orders( $prestation_id, $prestation, $update ) {
		return;

		// if( wp_cache_get(__CLASS__ . '-' . __FUNCTION__ . '-' . $prestation_id) ) return;
		// $prestation = Mltp_Prestation::get_post($prestation);
		// if(!$prestation) return;
		// // if(! Mltp_Prestation::is_prestation_post($prestation) && isset($prestation->post)) $prestation = $prestation->post;
		// // if(! Mltp_Prestation::is_prestation_post($prestation)) return;
		// // if( $prestation->post_type != 'prestation' ) return;
		// if( $prestation->post_status == 'trash' ) return; // TODO: remove prestation reference from orders
		//
		// $orders = wc_get_orders( array(
		// 'limit'        => -1, // Query all orders
		// 'orderby'      => 'date',
		// 'order'        => 'ASC',
		// 'meta_key'     => 'prestation_id',
		// 'meta_value' => $prestation_id,
		// ) );
		//
		// $p_orders_total = 0;
		// $p_orders_paid = 0;
		// $p_orders_discount = 0;
		// $p_orders_refunded = 0;
		// $p_orders_subtotal = 0;
		//
		// $payment_products = Mltp_Payment_Product::get_payment_products();
		// if(!is_array($payment_products)) $payment_products = [ $payment_products ];
		// $excl_tax = false;
		//
		// $dates = [];
		// $lines = [];
		// foreach ($orders as $key => $order) {
		// $order_dates = [];
		// $excl_tax = ($order->prices_include_tax == true) ? false : true;
		//
		// $p_order = array(
		// 'id' => $order->get_id(),
		// 'source' => 'WooCommerce',
		// 'object' => __CLASS__,
		// 'created' => $order->get_date_created(),
		// 'subtotal' => ($excl_tax == true) ? $order->get_subtotal() : (float)wp_strip_all_tags(preg_replace('/"woocommerce-Price-currencySymbol">[^<]*</', '><', $order->get_subtotal_to_display())),
		// 'discount' => $order->get_total_discount($excl_tax),
		// 'refunded' => $order->get_total_refunded(),
		// 'total' => $order->get_total() - $order->get_total_refunded(),
		// 'paid' => NULL,
		// 'status' =>  $order->get_status(),
		// 'view_url' => $order->get_view_order_url(),
		// 'edit_url' => $order->get_edit_order_url(),
		// );
		// $p_order['paid'] = ($order->get_date_paid()) ? $p_order['total'] : 0;
		// $p_order['paid'] = (in_array($order->get_status(), [ 'completed', 'processing' ])) ? $p_order['total'] : 0;
		//
		//
		// $order_dates = array_filter($order_dates);
		//
		// $p_order['from'] = (!empty($order_dates)) ? min($order_dates) : NULL;
		// $p_order['to'] = (!empty($order_dates)) ? max($order_dates) : NULL;
		// if($p_order['to'] == $p_order['from']) $p_order['to'] = NULL;
		//
		// $dates[] = $p_order['from'];
		// $dates[] = $p_order['to'];
		//
		// $p_order['description'] = $p_order['items'][0]['product_name']
		// . ( (count($p_order['items']) > 1) ? sprintf( __(' + %s items', 'multipass' ), count($p_order['items']) - 1 ) : '' );
		//
		// $lines[] = $p_order;
		//
		// $p_orders[$order->get_id()] = $p_order;
		//
		// $p_orders_subtotal += $p_order['subtotal'];
		// $p_orders_discount += $p_order['discount'];
		// $p_orders_refunded += $p_order['refunded'];
		// $p_orders_total += $p_order['total'];
		// $p_orders_paid += $p_order['paid'];
		// }
		//
		// $dates = array_filter($dates);
		// if(!empty($dates)) {
		// $dates = array_unique(array(min($dates), max($dates)));
		// }
		// update_post_meta( $prestation_id, 'modules-data', array(
		// 'subtotal' => $p_orders_subtotal,
		// 'discount' => $p_orders_discount,
		// 'total' => $p_orders_total,
		// 'paid' => $p_orders_paid,
		// 'refunded' => $p_orders_refunded,
		// 'dates' => $dates,
		// 'orders' => $p_orders,
		// 'rows' => $lines,
		// ) );
		//
		// $prestation_post = get_post($prestation_id);
		// if(is_object($prestation) && $prestation->post_type == 'prestation')
		// Mltp_Prestation::update_prestation_amounts($prestation_id, $prestation, true );
		//
		// // $metas = get_post_meta($prestation_id, 'modules-data');
		// // error_log(print_r($metas, true));
		// // $metas['woocommerce'] = $lines;
		// // foreach($metas as $key => $meta) {
		// // 	if(!isset($meta['source']) || $meta['source'] == 'woocommerce') {
		// // 		unset($meta[$key]);
		// // 	}
		// // }
		// // // $metas = array_merge($metas, $p_orders);
		// // $metas = $lines;
		//
		// // update_post_meta($prestation_id, 'managed', $metas);
		//
		// wp_cache_set(__CLASS__ . '-' . __FUNCTION__ . '-' . $prestation_id, true);
	}

	static function update_order_prestation( $post_id, $post, $update ) {
		if ( $post->post_type != 'shop_order' ) {
			return;
		}
		if ( $post->post_status == 'trash' ) {
			return; // TODO: update previously linked prestation
		}

		// remove_action(current_action(), __CLASS__ . '::wp_insert_post_action');

		$prestation_id = get_post_meta( $post_id, 'prestation_id', true );
		$customer_id   = get_post_meta( $post_id, '_customer_user', true );
		$customer      = get_user_by( 'id', $customer_id );
		if ( $customer ) {
			$customer_name  = $customer->display_name;
			$customer_email = $customer->user_email;
			$customer_phone = array_unique(
				array_filter(
					array(
						get_user_meta( $customer_id, 'billing_phone', true ),
						get_user_meta( $customer_id, 'shipping_phone', true ),
					)
				)
			);
			// $customer_phone = trim(get_post_meta($post_id, '_billing_phone', true);
			// error_log("customer " . print_r($customer, true));
		} else {
			$customer_name  = trim( get_post_meta( $post_id, '_billing_first_name', true ) . ' ' . get_post_meta( $post_id, '_billing_last_name', true ) );
			$customer_email = get_post_meta( $post_id, '_billing_email', true );
			$customer_phone = array_unique(
				array_filter(
					array(
						get_post_meta( $post_id, '_billing_phone', true ),
						get_post_meta( $post_id, '_shipping_phone', true ),
					)
				)
			);
		}

		$prestation = new Mltp_Prestation(
			array(
				'prestation_id'  => $prestation_id,
				'customer_id'    => $customer_id,
				'customer_name'  => $customer_name,
				'customer_email' => $customer_email,
				'date'           => esc_attr( $post->post_date ),
				'date_gmt'       => esc_attr( $post->post_date_gmt ),
			)
		);
		if ( $prestation ) {
			update_post_meta( $post_id, 'prestation_id', $prestation->ID );
			self::update_prestation_orders( $prestation->ID, $prestation, true );

			// TODO: mark parts related to this order as review in progress

			$order = wc_get_order( $post_id ); // make sure it is a wc object, not only a post
			foreach ( $order->get_items() as $item_id => $item ) {
				$product    = $item->get_product();
				$product_id = $product->get_id();

				$terms = get_the_terms( $product_id, 'product_cat' );
				if ( $terms ) {
					$category = $terms[0]->name;
				}

				$description = join(
					' ',
					array_filter(
						array(
							isset( $category ) ? $category : '',
							$item->get_name(),
							isset( $variation ) ? $variation->get_formatted_name() : '',
						)
					)
				);

				$dates = array();
				// $attendees = [];
				if ( $product->is_type( 'booking' ) ) {
					$booking_ids = WC_Booking_Data_Store::get_booking_ids_from_order_item_id( $item_id );
					foreach ( $booking_ids as $booking_id ) {
						$booking = get_wc_booking( $booking_id );
						// $datetimes[] = esc_html( apply_filters( 'wc_bookings_summary_list_date', date_i18n( $date_format, $booking->get_start() ), $booking->get_start(), $booking->get_end() ) );
						$dates['from'] = $booking->get_start();
						$dates['to']   = $booking->get_end();
					}
					$description .= ' ' . MultiPass::format_date_range( $dates, 'SHORT' );

					// TODO: get attendees and beds counts
					//
					// 'id'     => $prefix . 'attendees',
					// 'id'            => $prefix . 'total',
					// 'id'   => $prefix . 'adults',
					// 'id'   => $prefix . 'children',
					// 'id'   => $prefix . 'babies',
					//
					// 'id'     => $prefix . 'beds',
					// 'id'   => $prefix . 'double',
					// 'id'   => $prefix . 'single',
					// 'id'   => $prefix . 'baby',
				}

				$sub_total  = $item->get_subtotal() + $item->get_subtotal_tax();
				$quantity   = $item->get_quantity();
				$unit_price = ( empty( $quantity ) ) ? $sub_total : $sub_total / $quantity;
				$total      = $item->get_total() + $item->get_total_tax();
				$discount   = ( $total != $sub_total ) ? array( 'amount' => $sub_total - $total ) : array();
				$paid       = ( in_array( $order->get_status(), array( 'completed', 'processing' ) ) ) ? $total : 0;
				$balance    = $total - $paid;

				$type = ( Mltp_Payment_Product::is_payment_product( $product ) ) ? 'payment' : $product->get_type();
				switch ( $type ) {
					case 'booking':
						$description = '[' . __( 'Booking', 'multipass' ) . '] ' . $description;
						break;

					case 'payment':
						$description = '[' . __( 'Payment', 'multipass' ) . '] ' . $description;
						break;
				}

				$args = array(
					'source'         => 'woocommerce',
					'source_id'      => "$post_id",
					'source_item_id' => "$item_id",
					'view_url'       => $order->get_view_order_url(),
					'edit_url'       => $order->get_edit_order_url(),
					// 'source_details' => array(
					// 'wc_order_id' => $post_id,
					// 'wc_order_item_id' => $item_id,
					// 'wc_product_id' => $product_id,
					// 'wc_variation_id' => $item->get_variation_id(),
					// ),
					'description'    => "$description",

					'prestation_id'  => $prestation->ID,

					'customer'       => array(
						'user_id' => $customer_id,
						'name'    => $customer_name,
						'email'   => $customer_email,
						'phone'   => join( ', ', $customer_phone ),
					),
					// 'attendee' => array(
					// 'user_id' => $customer_id,
					// 'name' => $customer_name,
					// 'email' => $customer_email,
					// 'phone' => join(', ', $customer_phone),
					// ),
					'dates'          => $dates,
					// 'attendees' => $attendees;
					// 'beds' => $beds;

					'price'          => array(
						'quantity'  => $quantity,
						'unit'      => $unit_price,
						'sub_total' => $sub_total,
					),
					'discount'       => $discount,
					'total'          => $total,
					// TODO: ensure paid status is updated immediatly, not after second time save
					//
					'paid'           => $paid,
					'balance'        => $balance,
					'type'           => $type,

				);

				$prestation_item = new Mltp_Item( $args );
				// $prestation_item->update($args);
				// error_log ("prestation-item " . print_r($prestation_item->ID, true));

				// $lock = array_keys($part); // TODO: prevent modifications of locked fields

				// TODO: add lines for order discount, deposit, paid
				// 'id'            => $prefix . 'deposit',
				// 'id'      => $prefix . 'percent',
				// 'id'      => $prefix . 'amount',
				// 'id'          => $prefix . 'before',
				// 'id'     => $prefix . 'payment',
				// 'id'   => $prefix . 'date',
				// 'id'   => $prefix . 'amount',
				// 'id'   => $prefix . 'method',
				// 'id'   => $prefix . 'reference',
				// 'id'            => $prefix . 'deposit_amount',
				// 'id'            => $prefix . 'paid',
				// 'id'            => $prefix . 'balance',
				// 'id'            => $prefix . 'status',
				//
				//
				// if(Mltp_Payment_Product::is_payment_product($product)) {
				// $p_order['subtotal'] -= $item->get_subtotal();
				// $p_order['refunded'] -= $post->get_total_refunded_for_item($item_id);
				// $p_order['total'] = $p_order['total'] - $item->get_total() + $post->get_total_refunded_for_item($item_id);
				// }
			}
			//
			// TODO: delete remaining "review in progress" parts

		}

		// add_action(current_action(), __CLASS__ . '::wp_insert_post_action', 10, 3);
		return;
	}

	static function sync_orders_validation( $value, $field, $oldvalue ) {
		if ( $value == true ) {
			self::sync_orders();
		}

		return false; // sync_order field should never be saved
	}

	static function sync_orders() {
		$orders = wc_get_orders(
			array(
				'limit'   => -1, // Query all orders
				'orderby' => 'date',
				'order'   => 'ASC',
			// 'meta_key'     => 'prestation_id', // The postmeta key field
			// 'meta_compare' => 'NOT EXISTS', // The comparison argument
			)
		);
		foreach ( $orders as $key => $order ) {
			$order_post = get_post( $order->get_id() );
			self::update_order_prestation( $order_post->ID, $order_post, true );
		}
	}

	static function managed_list_filter( $html = '' ) {
		$title = __( 'Online Shop (WooCommerce)', 'multipass' );
		if ( empty( $list ) ) {
			$list = __( 'Empty list', 'multipass' );
		}

		global $post;

		$data            = get_post_meta( $post->ID, 'modules-data', true );
		$data['columns'] = array(
			'id'          => __( 'ID', 'multipass' ),
			'created'     => __( 'Created', 'multipass' ),
			'source'      => __( 'Source', 'multipass' ),
			'description' => __( 'Description', 'multipass' ),
			'from'        => __( 'From', 'multipass' ),
			'to'          => __( 'To', 'multipass' ),
			'subtotal'    => __( 'Subtotal', 'multipass' ),
			'discount'    => __( 'Discount', 'multipass' ),
			'refunded'    => __( 'Refunded', 'multipass' ),
			'total'       => __( 'Total', 'multipass' ),
			'paid'        => __( 'Paid', 'multipass' ),
			'status'      => __( 'Status', 'multipass' ),
			'actions'     => '',
		);
		$data['format']  = array(
			'created'  => 'date_time',
			'from'     => 'date',
			'to'       => 'date',
			'subtotal' => 'price',
			'discount' => 'price',
			'refunded' => 'price',
			'total'    => 'price',
			'paid'     => 'price',
			'status'   => 'status',
		);

		$list = new Mltp_Table( $data );

		$html .= sprintf(
			'
		<div class="managed-list managed-list-woocommerce">
			<h3>%s</h3>
			%s
		</div>',
			$title,
			$list->render(),
		);
		return $html;
	}

}

$this->modules[] = new Mltp_WooCommerce();