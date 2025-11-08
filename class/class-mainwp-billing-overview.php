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
			<a href="admin.php?page=Extensions-Billing-For-Mainwp-Main&tab=dashboard" class="item <?php echo ( $current_tab == 'dashboard' ) ? 'active' : ''; ?>"><i class="tasks icon"></i> <?php esc_html_e( 'Dashboard', 'mainwp-billing-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Billing-For-Mainwp-Main&tab=settings" class="item <?php echo ( $current_tab == 'settings' || $current_tab == '' ) ? 'active' : ''; ?>"><i class="file alternate outline icon"></i> <?php esc_html_e( 'Settings', 'mainwp-billing-extension' ); ?></a>
		</div>
		<?php

		if ( $current_tab == 'settings' ) {
			self::render_settings();
		} else {
			self::render_dashboard();
		}
	}

    /**
     * Render the Dashboard page content with the records table and client filter.
     * (Req #5, #6)
     *
     * @return void
     */
    public static function render_dashboard() {
		// Fetch data
		$qb_client_filter = isset( $_GET['qb_client'] ) ? sanitize_text_field( wp_unslash( $_GET['qb_client'] ) ) : '';

		$params = array();
		if ( ! empty( $qb_client_filter ) ) {
			$params['qb_client_name'] = $qb_client_filter;
		}

		$records = MainWP_Billing_DB::get_instance()->get_billing_records( $params );

		// Fetch all unique client names for the filter dropdown
		$all_records = MainWP_Billing_DB::get_instance()->get_billing_records();
		$unique_qb_clients = array_unique( wp_list_pluck( $all_records, 'qb_client_name' ) );
		sort( $unique_qb_clients );

		// Fetch all MainWP sites for the manual map dropdown (Req #5)
		$all_mainwp_sites = MainWP_Billing_Utility::get_websites();
		// Create an associative array (id => name) for the dropdown.
		$mainwp_sites_map = array();
		foreach ( $all_mainwp_sites as $site ) {
			$mainwp_sites_map[ $site->id ] = $site->name;
		}

        ?>
		<div class="ui segment">
			<h2 class="ui header"><?php esc_html_e( 'Recurring Billing Dashboard', 'mainwp-billing-extension' ); ?></h2>
			<div class="ui divider"></div>

			<form method="get" action="admin.php">
				<input type="hidden" name="page" value="Extensions-Billing-For-Mainwp-Main" />
				<input type="hidden" name="tab" value="dashboard" />
				<div class="ui grid stackable">
					<div class="eight wide column">
						<div class="field">
							<label><?php esc_html_e( 'Filter by QuickBooks Client Name', 'mainwp-billing-extension' ); ?></label>
							<select class="ui dropdown" name="qb_client" onchange="this.form.submit()">
								<option value=""><?php esc_html_e( 'Show All Clients', 'mainwp-billing-extension' ); ?></option>
								<?php foreach ( $unique_qb_clients as $client_name ) : ?>
									<option value="<?php echo esc_attr( $client_name ); ?>" <?php selected( $qb_client_filter, $client_name ); ?>>
										<?php echo esc_html( $client_name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<div class="eight wide column">
                        <div class="field">
                            <label>&nbsp;</label>
                            <a href="admin.php?page=Extensions-Billing-For-Mainwp-Main&tab=dashboard" class="ui basic button"><?php esc_html_e( 'Clear Filter', 'mainwp-billing-extension' ); ?></a>
                        </div>
					</div>
				</div>
			</form>

			<div class="ui divider"></div>

			<?php if ( empty( $records ) ) : ?>
				<div class="ui info message">
					<?php esc_html_e( 'No recurring billing records found. Please import a CSV file via the Settings tab.', 'mainwp-billing-extension' ); ?>
				</div>
			<?php else : ?>

				<table class="ui celled unstackable table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'QuickBooks Client Name', 'mainwp-billing-extension' ); ?></th>
							<th><?php esc_html_e( 'Template Name', 'mainwp-billing-extension' ); ?></th>
							<th><?php esc_html_e( 'Next Date', 'mainwp-billing-extension' ); ?></th>
							<th><?php esc_html_e( 'Amount', 'mainwp-billing-extension' ); ?></th>
							<th><?php esc_html_e( 'Mapped Site', 'mainwp-billing-extension' ); ?></th>
							<th class="right aligned"><?php esc_html_e( 'Manual Map/Update', 'mainwp-billing-extension' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $records as $record ) : ?>
							<tr>
								<td><?php echo esc_html( $record->qb_client_name ); ?></td>
								<td><?php echo esc_html( $record->template_name ); ?></td>
								<td><?php echo MainWP_Billing_Utility::format_date( strtotime( $record->next_date ) ); ?></td>
								<td><?php echo esc_html( '$' . number_format( floatval( $record->amount ), 2 ) ); ?></td>
								<td>
									<?php
									if ( $record->mainwp_site_id > 0 ) {
										echo '<a href="' . esc_url( 'admin.php?page=managesites&dashboard=' . $record->mainwp_site_id ) . '" target="_blank">' . esc_html( $record->site_name ) . '</a>';
									} else {
										echo '<span class="ui red label">' . esc_html__( 'Unmapped', 'mainwp-billing-extension' ) . '</span>';
									}
									?>
								</td>
								<td class="right aligned">
									<select class="ui dropdown mainwp-billing-site-select" data-record-id="<?php echo intval( $record->id ); ?>">
										<option value="0"><?php esc_html_e( '-- Select Site --', 'mainwp-billing-extension' ); ?></option>
										<?php
										foreach ( $mainwp_sites_map as $site_id => $site_name ) {
											$selected = selected( $record->mainwp_site_id, $site_id, false );
											echo '<option value="' . intval( $site_id ) . '" ' . $selected . '>' . esc_html( $site_name ) . '</option>';
										}
										?>
									</select>
									<button class="ui tiny green button mainwp-billing-map-button" data-record-id="<?php echo intval( $record->id ); ?>" style="display:none;"><?php esc_html_e( 'Map', 'mainwp-billing-extension' ); ?></button>
                                    <i class="ui small check circle icon green mainwp-billing-mapped-check" style="display:none;"></i>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

			<?php endif; ?>

		</div>
        <?php
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

            <form method="post" action="admin.php?page=Extensions-Billing-For-Mainwp-Main&tab=settings" enctype="multipart/form-data" class="ui form">
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
