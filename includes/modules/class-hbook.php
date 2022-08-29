<?php

/**
 * Register all actions and filters for the plugin
 *
 * @link       http://example.com
 * @since      0.1.0
 *
 * @package    Prestations
 * @subpackage Prestations/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Prestations
 * @subpackage Prestations/includes
 * @author     Your Name <email@example.com>
 */
class Prestations_HBook extends Prestations_Modules {

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
		// register_activation_hook( PRESTATIONS_FILE, __CLASS__ . '::activate' );
		// register_deactivation_hook( PRESTATIONS_FILE, __CLASS__ . '::deactivate' );
	}

	/**
	 * Register the filters and actions with WordPress.
	 *
	 * @since    0.1.0
	 */
	public function run() {

		$this->actions = array(
		);

		$this->filters = array(
			array (
				'hook' => 'mb_settings_pages',
				'callback' => 'register_settings_pages',
			),
			array(
				'hook' => 'rwmb_meta_boxes',
				'callback' => 'register_fields'
			),
		);

		$defaults = array( 'component' => __CLASS__, 'priority' => 10, 'accepted_args' => 1 );

		foreach ( $this->filters as $hook ) {
			$hook = array_merge($defaults, $hook);
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			$hook = array_merge($defaults, $hook);
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

	}

	static function register_settings_pages( $settings_pages ) {
		$settings_pages['prestations']['tabs']['hbook'] = 'HBook';
		// error_log(__CLASS__ . ' tabs ' . print_r($settings_pages['prestations']['tabs'], true));

		return $settings_pages;
	}

	static function register_fields( $meta_boxes ) {
		$prefix = 'hbook_';
		$hbook = new Prestations_HBook();

		// Lodify Settings tab
    $meta_boxes[] = [
        'title'          => __( 'HBook Settings', 'prestations' ),
        'id'             => 'hbook-settings',
        'settings_pages' => ['prestations'],
        'tab'            => 'hbook',
        'fields'         => [
						[
							'name'              => __( 'Sync bookings', 'prestations' ),
							'id'                => $prefix . 'sync_bookings',
							'type'              => 'switch',
							'desc'              => __( 'Sync HBook bookings with prestations, create prestation if none exist. Only useful after plugin activation or if out of sync.', 'prestations' ),
							'style'             => 'rounded',
							'sanitize_callback' => 'Prestations_HBook::sync_bookings',
							'save_field' => false,
						],
        ],
    ];

		$meta_boxes['associations']['fields'][] = [
			'name'       => __( 'HBook Property', 'prestations' ),
			'id'         => 'association_hbook_id',
			'type'       => 'select_advanced',
			'options'	=> $hbook->get_property_options(),
			'admin_columns' => [
					'position'   => 'before date',
					'sort'       => true,
					'searchable' => true,
			],
			'columns' => 3,
		];

		return $meta_boxes;
	}

	function get_properties() {
		$transient_key = sanitize_title(__CLASS__ . '-' . __FUNCTION__);
		$properties = get_transient($transient_key);
		if($properties) return $properties;

		$properties = wp_cache_get($transient_key);
		if($properties) return $properties;

		error_log('fetching properties');
		$posts = get_posts(array(
			'numberposts' => -1,
			'post_type' => 'hb_accommodation',
			'post_status' => 'publish',
			'orderby'	=> 'name',
		));
		$properties = [];
		foreach($posts as $key => $post) {
			if(preg_match('/"translated/', $post->post_content)) continue;
			$properties[$post->ID] = $post;
		}
		error_log('fetching properties ' . print_r($properties, true));

		wp_cache_set($transient_key, $properties);
		// set_transient($transient_key, $properties, 3600);
		return $properties;
	}

	function get_property_options() {
		$options = [];
		$properties = $this->get_properties();
		if($properties) foreach($properties as $id => $property) {
			$options[$property->ID] = $property->post_title;
		}
		return $options;
	}
}

$this->modules[] = new Prestations_HBook();