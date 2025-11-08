<?php
/**
 * MainWP Billing Overview
 *
 * This class handles the Overview process.
 *
 * @package MainWP/Extensions
 */

 namespace MainWP\Extensions\Billing;

 /**
  * Class MainWP_Billing_Overview
  *
  * @package MainWP/Extensions
  */
class MainWP_Billing_Overview {

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
	 * MainWP_Billing_Overview constructor.
     *
     * @return void
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

		$this->handle_settings_post();

	}

	/**
	 * Handle settings post.
	 *
	 * @return void
	 */
	public function handle_settings_post() {
		// Only proceed if the import button was clicked
		if ( isset( $_POST['submit_import_billing_data'] ) ) {

			// Nonce security check
			if ( ! isset( $_POST['mainwp_billing_settings_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['mainwp_billing_settings_nonce'] ), 'mainwp_billing_settings' ) ) {
				MainWP_Billing_Utility::log_info( 'Import failed: Nonce check failed.' );
				// translators: %s: message.
				MainWP_Billing_Utility::update_setting( 'import_message', sprintf( esc_html__( 'Import failed: %s', 'mainwp-billing-extension' ), esc_html__( 'Security check failed.', 'mainwp-billing-extension' ) ) );
				return;
			}

			// File existence check
			if ( empty( $_FILES['billing_csv_file']['tmp_name'] ) ) {
				MainWP_Billing_Utility::log_info( 'Import failed: No file uploaded.' );
				// translators: %s: message.
				MainWP_Billing_Utility::update_setting( 'import_message', sprintf( esc_html__( 'Import failed: %s', 'mainwp-billing-extension' ), esc_html__( 'No file selected.', 'mainwp-billing-extension' ) ) );
				return;
			}

			$file = wp_unslash( $_FILES['billing_csv_file'] );

			// Basic file extension check
			$file_ext = pathinfo( $file['name'], PATHINFO_EXTENSION );
			if ( 'csv' !== strtolower( $file_ext ) ) {
				MainWP_Billing_Utility::log_info( 'Import failed: Invalid file type.' );
				// translators: %s: message.
				MainWP_Billing_Utility::update_setting( 'import_message', sprintf( esc_html__( 'Import failed: %s', 'mainwp-billing-extension' ), esc_html__( 'Please upload a CSV file.', 'mainwp-billing-extension' ) ) );
				return;
			}

			// Process the file using the DB class
			$result = MainWP_Billing_DB::get_instance()->import_billing_csv( $file['tmp_name'] );

			// Handle the result of the import
			if ( is_wp_error( $result ) ) {
				$message = $result->get_error_message();
				MainWP_Billing_Utility::update_setting( 'import_message', '<div class="ui red message">' . sprintf( esc_html__( 'Import failed: %s', 'mainwp-billing-extension' ), $message ) . '</div>' );
				MainWP_Billing_Utility::log_info( 'Import failed: ' . $message );
			} else {
				$msg = sprintf( esc_html__( 'Import successful! Records added: %d, Updated: %d, Removed: %d, Skipped: %d', 'mainwp-billing-extension' ),
					( isset( $result['added'] ) ? intval( $result['added'] ) : 0 ),
					( isset( $result['updated'] ) ? intval( $result['updated'] ) : 0 ),
					( isset( $result['removed'] ) ? intval( $result['removed'] ) : 0 ),
					( isset( $result['skipped'] ) ? intval( $result['skipped'] ) : 0 )
				);
				MainWP_Billing_Utility::update_setting( 'import_message', '<div class="ui green message">' . $msg . '</div>' );
				MainWP_Billing_Utility::update_setting( 'last_imported_timestamp', time() ); // Req #9
				MainWP_Billing_Utility::log_info( 'Import successful. ' . $msg );
			}
		}
	}

	/**
	 * Render extension page tabs.
     *
     * @return void
     */
	public static function render_tabs() {

		$current_tab = 'dashboard';

		if ( isset( $_GET['tab'] ) ) {
			if ( $_GET['tab'] == 'dashboard' ) {
				$current_tab = 'dashboard';
			} elseif ( $_GET['tab'] == 'settings' ) {
				$current_tab = 'settings';
			}
		}

		?>

		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-pro-billing-menu">
			<a href="admin.php?page=Extensions-Mainwp-Billing-Extension&tab=dashboard" class="item <?php echo ( $current_tab == 'dashboard' ) ? 'active' : ''; ?>"><i class="tasks icon"></i> <?php esc_html_e( 'Dashboard', 'mainwp-billing-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Billing-Extension&tab=settings" class="item <?php echo ( $current_tab == 'settings' || $current_tab == '' ) ? 'active' : ''; ?>"><i class="file alternate outline icon"></i> <?php esc_html_e( 'Settings', 'mainwp-billing-extension' ); ?></a>
		</div>
		<?php

		if ( $current_tab == 'settings' ) {
			self::render_settings();
		} else {
			?>
            <div>Dashboard Page Placeholder</div>
			<?php
		}
	}

    /**
     * Render the settings page content with CSV import form.
     *
     * @return void
     */
    public static function render_settings() {
        $utility = MainWP_Billing_Utility::get_instance();
        $last_imported = $utility->get_setting( 'last_imported_timestamp' );
        $import_message = $utility->get_setting( 'import_message' );
        // Clear message after display.
        $utility->update_setting( 'import_message', '' );

        ?>
        <div class="ui segment">
            <h2 class="ui header"><?php esc_html_e( 'QuickBooks CSV Import', 'mainwp-billing-extension' ); ?></h2>
            <div class="ui divider"></div>

            <?php if ( ! empty( $import_message ) ) : ?>
                <?php echo $import_message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped (already escaped with div wrapper in handle_settings_post) ?>
            <?php endif; ?>

            <form method="post" action="admin.php?page=Extensions-Mainwp-Billing-Extension&tab=settings" enctype="multipart/form-data" class="ui form">
                <?php wp_nonce_field( 'mainwp_billing_settings', 'mainwp_billing_settings_nonce' ); ?>

                <div class="field">
                    <label><?php esc_html_e( 'Upload Recurring Transactions CSV', 'mainwp-billing-extension' ); ?></label>
                    <div class="ui action input">
                        <input type="file" name="billing_csv_file" id="billing_csv_file" accept=".csv" required>
                    </div>
                    <p class="ui basic message">
                        <?php esc_html_e( 'The CSV must contain the columns: "Transaction Type", "Template Name", "Previous date", "Next Date", "Name", "Memo/Description", "Account", and "Amount".', 'mainwp-billing-extension' ); ?>
                    </p>
                </div>

                <input type="submit" name="submit_import_billing_data" id="submit_import_billing_data" class="ui green button" value="<?php esc_html_e( 'Upload and Import Data', 'mainwp-billing-extension' ); ?>" />
            </form>

            <div class="ui divider"></div>

            <h3 class="ui header"><?php esc_html_e( 'Import Status', 'mainwp-billing-extension' ); ?></h3>
            <p>
                <strong><?php esc_html_e( 'Last Imported Date:', 'mainwp-billing-extension' ); ?></strong>
                <?php
                if ( ! empty( $last_imported ) ) {
                    echo MainWP_Billing_Utility::format_timestamp( $last_imported ); // Req #9
                } else {
                    esc_html_e( 'Never imported.', 'mainwp-billing-extension' );
                }
                ?>
            </p>
        </div>
        <?php
    }
}
