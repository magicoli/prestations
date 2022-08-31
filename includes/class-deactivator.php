<?php

/**
 * Fired during plugin deactivation
 *
 * @link       http://example.com
 * @since      0.1.0
 *
 * @package    MultiServices
 * @subpackage MultiServices/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      0.1.0
 * @package    MultiServices
 * @subpackage MultiServices/includes
 * @author     Your Name <email@example.com>
 */
class MultiServices_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    0.1.0
	 */
	public static function deactivate() {
    flush_rewrite_rules();
	}

}
