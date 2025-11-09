<?php
/**
 * MainWP Billing
 *
 * This class handles the extension process.
 *
 * @package MainWP/Extensions
 */

 namespace MainWP\Extensions\Billing;

 /**
  * Class MainWP_Billing
  *
  * @package MainWP/Extensions
  */
class MainWP_Billing_Ajax {

	/**
	 * @var string The update version.
	 */
	public $update_version = '1.0';

	/**
	 * @var self|null The singleton instance of the class.
	 */
	private static $instance = null;

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
	 * MainWP_Billing_Ajax constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
	}

	/**
     * Admin init.
     *
	 * @return void
	 */
	public function admin_init() {
        /**
		 * Example MainWP AJAX actions.
		 */
		do_action( 'mainwp_ajax_add_action', 'mainwp_billing_do_something', array( &$this, 'ajax_do_something' ) );
        // Removed: do_action( 'mainwp_ajax_add_action', 'mainwp_billing_map_site', array( &$this, 'ajax_map_site' ) );
        do_action( 'mainwp_ajax_add_action', 'mainwp_billing_clear_data', array( &$this, 'ajax_clear_data' ) ); // New: Clear Data
	}

    /**
     * Ajax reload data.
     *
     * @return void
     */
	public function ajax_do_something() {

		do_action( 'mainwp_secure_request', 'mainwp_billing_do_something' );
		// Do your PHP Work here then return the results via wp_send_json.
	}

    /**
     * Ajax action to clear all billing data. (Req #4)
     *
     * @return void
     */
	public function ajax_clear_data() {
		do_action( 'mainwp_secure_request', 'mainwp_billing_clear_data' );

		$result = MainWP_Billing_DB::get_instance()->clear_all_data();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'error' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => esc_html__( 'All billing data cleared successfully.', 'mainwp-billing-extension' ) ) );
	}
}
