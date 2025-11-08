<?php
/**
 * MainWP Billing DB
 *
 * This class handles the DB process.
 *
 * @package MainWP/Extensions
 */

 namespace MainWP\Extensions\Billing;

 /**
  * Class MainWP_Billing_DB
  *
  * @package MainWP/Extensions
  */
class MainWP_Billing_DB {

	/**
	 * @var self|null The singleton instance of the class.
	 */
	private static $instance = null;

	/**
	 * @var \wpdb $wpdb WordPress database object.
	 */
	private $wpdb;

	/**
	 * Get the singleton instance.
	 *
	 * @return self|null
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * MainWP_Billing_DB constructor.
	 *
	 * @return void
	 */
	public function __construct() {

	}

	/**
	 * Install Extension.
	 *
	 * @return void
	 */
	public function install() {
		global $wpdb;

		$current_version = get_option( 'mainwp_billing_db_version' );
		$version_to_update = '1.0'; // Current DB schema version

		if ( $current_version == $version_to_update ) {
			return; // No update needed.
		}

		$table_name = $wpdb->prefix . 'mainwp_billing_records';

		$sql = '';

		// mainwp_billing_records table
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) != $table_name || version_compare( $current_version, '1.0', '<' ) ) {
			$collate = $wpdb->get_charset_collate();
			$sql .= "CREATE TABLE {$table_name} (
				id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				template_name VARCHAR(255) NOT NULL,
				qb_client_name VARCHAR(255) NOT NULL,
				previous_date DATE NOT NULL,
				next_date DATE NOT NULL,
				amount DECIMAL(10,2) NOT NULL DEFAULT '0.00',
				mainwp_site_id INT(11) UNSIGNED NOT NULL DEFAULT '0',
				last_imported INT(11) UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY (id),
				UNIQUE KEY template_name (template_name),
				KEY mainwp_site_id (mainwp_site_id)
			) $collate;";
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'mainwp_billing_db_version', $version_to_update );
	}
}
