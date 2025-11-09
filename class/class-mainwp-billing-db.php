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
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * Get table name.
	 *
	 * @param string $suffix Table suffix.
	 *
	 * @return string Table name.
	 */
	public function get_table_name( $suffix = '' ) {
		return $this->wpdb->prefix . 'mainwp_billing_' . $suffix;
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

		$table_name = $this->get_table_name( 'records' );

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

	/**
	 * Clears all records from the billing database.
	 *
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	public function clear_all_data() {
		$table_records = $this->get_table_name( 'records' );
		$deleted = $this->wpdb->query( "TRUNCATE TABLE {$table_records}" );

		if ( false === $deleted ) {
			return new \WP_Error( 'db_error', esc_html__( 'Failed to clear billing records.', 'mainwp-billing-extension' ) );
		}

		// Clear the last imported timestamp as well
		MainWP_Billing_Utility::get_instance()->update_setting( 'last_imported_timestamp', 0 );

		return true;
	}

	/**
	 * Retrieve all MainWP clients.
	 *
	 * @return array Array of client objects (id, name).
	 */
	public function get_all_clients() {
		$table_clients = $this->wpdb->prefix . 'mainwp_wp_clients';

        // Check if the client table exists first.
        if( $this->wpdb->get_var( "SHOW TABLES LIKE '{$table_clients}'" ) != $table_clients ) {
            return array();
        }

		$sql = "SELECT client_id AS id, name FROM {$table_clients} ORDER BY name ASC";
		$results = $this->wpdb->get_results( $sql );
		return ( is_array( $results ) ) ? $results : array();
	}

	/**
	 * Get all unique MainWP site IDs that have a billing record assigned.
	 *
	 * @return array Array of integers (mainwp_site_id).
	 */
	public function get_all_mapped_site_ids() {
		$table_name = $this->get_table_name( 'records' );
		// Only get IDs > 0, as 0 means unmapped.
		$mapped_ids = $this->wpdb->get_col( "SELECT DISTINCT mainwp_site_id FROM {$table_name} WHERE mainwp_site_id > 0" );

		if ( empty( $mapped_ids ) ) {
			return array();
		}

		// Ensure all IDs are integers.
		return array_map( 'intval', $mapped_ids );
	}

	/**
	 * Retrieve billing records, optionally filtered and joined with MainWP sites.
	 *
	 * @param array $params Query parameters (e.g., 'qb_client_name', 'mainwp_site_id').
	 *
	 * @return array Array of billing records.
	 */
	public function get_billing_records( $params = array() ) {
		$table_records = $this->get_table_name( 'records' );
		$table_sites   = $this->wpdb->prefix . 'mainwp_wp';

		$where = ' WHERE 1=1 ';
		$sql   = "SELECT rec.*, site.name AS site_name, site.url AS site_url, site.client_id AS mainwp_client_id
				FROM {$table_records} rec
				LEFT JOIN {$table_sites} site ON rec.mainwp_site_id = site.id";

		$sql_params = array();

		// Filter by QuickBooks Client Name (for Mapping tab)
		if ( isset( $params['qb_client_name'] ) && ! empty( $params['qb_client_name'] ) ) {
			$where .= ' AND rec.qb_client_name = %s ';
			$sql_params[] = $params['qb_client_name'];
		}

		// Filter by MainWP Site ID (for Individual site page)
		if ( isset( $params['mainwp_site_id'] ) && $params['mainwp_site_id'] > 0 ) {
			$where .= ' AND rec.mainwp_site_id = %d ';
			$sql_params[] = intval( $params['mainwp_site_id'] );
		}

		// Filter by Mapped Status (for Dashboard tab)
		if ( isset( $params['is_mapped'] ) ) {
			if ( $params['is_mapped'] ) {
				$where .= ' AND rec.mainwp_site_id > 0 ';
			} else {
				$where .= ' AND rec.mainwp_site_id = 0 ';
			}
		}

		// Filter by MainWP Client ID (for Dashboard tab)
		if ( isset( $params['mainwp_client_id'] ) && $params['mainwp_client_id'] > 0 ) {
			$where .= ' AND site.client_id = %d ';
			$sql_params[] = intval( $params['mainwp_client_id'] );
		}

		$sql .= $where . ' ORDER BY rec.qb_client_name ASC';

		if ( ! empty( $sql_params ) ) {
			$results = $this->wpdb->get_results( $this->wpdb->prepare( $sql, $sql_params ) );
		} else {
			$results = $this->wpdb->get_results( $sql );
		}

		return ( is_array( $results ) ) ? $results : array();
	}

	/**
	 * Updates the mainwp_site_id for a specific billing record.
	 *
	 * @param int $record_id The ID of the billing record.
	 * @param int $site_id The MainWP site ID to map to.
	 *
	 * @return int|\WP_Error Number of affected rows (0 or 1) or WP_Error on failure.
	 */
	public function update_site_map( $record_id, $site_id ) {
		$table_name = $this->get_table_name( 'records' );

		// Ensure site_id is explicitly an integer.
		$site_id = (int) $site_id;

		$updated = $this->wpdb->update(
			$table_name,
			array( 'mainwp_site_id' => $site_id ),
			array( 'id' => $record_id ),
			array( '%d' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			// A true database error occurred. Log it and return a detailed error.
			return new \WP_Error( 'db_error', $this->wpdb->last_error . ' (Failed to execute update query.)' );
		}
		
		// Return 1 if rows were affected, or 0 if the value was already correct (which is still a success for user intent).
		return (int) $updated;
	}


	/**
	 * Imports the billing data from the uploaded QuickBooks CSV file.
	 *
	 * @param string $file_path The temporary path to the uploaded CSV file.
	 *
	 * @return array|\WP_Error Import statistics or WP_Error on failure.
	 */
	public function import_billing_csv( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return new \WP_Error( 'file_not_found', esc_html__( 'Uploaded file not found.', 'mainwp-billing-extension' ) );
		}

		// Use the fgetcsv function with explicit parameters: delimiter (comma), no enclosure, no escape character.
		$handle = fopen( $file_path, 'r' );
		if ( false === $handle ) {
			return new \WP_Error( 'file_open_failed', esc_html__( 'Failed to open the uploaded file.', 'mainwp-billing-extension' ) );
		}

		$table_name = $this->get_table_name( 'records' );
		$import_stats = array(
			'added'   => 0,
			'updated' => 0,
			'skipped' => 0,
			'removed' => 0,
		);
		$current_import_time = time();
		$processed_template_names = array();

		// Expected core columns (Req #2).
		$expected_headers = array(
			'Template Name',
			'Previous date',
			'Next Date',
			'Name',
			'Amount',
		);
		$header_map = array(); // Will map expected column name to CSV column index.

		// Read the header row using fgetcsv. FIX: Use double quote as enclosure.
		$header_row = fgetcsv( $handle, 0, ',', '"' );
		if ( false === $header_row || null === $header_row ) {
			fclose( $handle );
			return new \WP_Error( 'empty_file', esc_html__( 'The uploaded file is empty or headers could not be read.', 'mainwp-billing-extension' ) );
		}

		// Normalize the actual headers for robust matching: trim whitespace and convert to lowercase.
		$normalized_headers = array_map( 'trim', $header_row );
		$normalized_headers = array_map( 'strtolower', $normalized_headers );

		// Map CSV columns to expected columns and validate core fields.
		foreach ( $expected_headers as $expected_col ) {
			// Normalize the expected column name for matching.
			$normalized_expected_col = strtolower( trim( $expected_col ) );

			// Search the normalized headers for the normalized expected column.
			$index = array_search( $normalized_expected_col, $normalized_headers );

			if ( false === $index ) {
				// We enforce the core fields based on your requirements.
				fclose( $handle );
				return new \WP_Error( 'missing_column', sprintf( esc_html__( 'Missing required column in CSV: %s', 'mainwp-billing-extension' ), $expected_col ) );
			}
			$header_map[ $expected_col ] = $index;
		}

		// Optimization: get all existing sites for auto-mapping logic (Req #4).
		$mainwp_sites = MainWP_Billing_Utility::get_websites();
		$site_names = array();
		foreach ( $mainwp_sites as $site ) {
			$site = (object) $site;
			$site_names[ strtolower( $site->name ) ] = $site->id;
			$site_names[ strtolower( MainWP_Billing_Utility::get_nice_url( $site->url, false ) ) ] = $site->id;
		}


		while ( ( $data = fgetcsv( $handle, 0, ',', '"' ) ) !== false ) { // FIX: Use double quote as enclosure.
			// Skip rows that are too short or empty.
			if ( count( $data ) < count( $header_row ) ) {
				continue;
			}

			// Sanitize and extract core data.
			$template_name = sanitize_text_field( $data[ $header_map['Template Name'] ] );
			$qb_client_name = sanitize_text_field( $data[ $header_map['Name'] ] );

			// Skip if core fields are empty.
			if ( empty( $template_name ) || empty( $qb_client_name ) ) {
				$import_stats['skipped']++;
				continue;
			}

			// Store the name to check for removals later.
			$processed_template_names[] = $template_name;

			// Convert dates to YYYY-MM-DD format (best effort).
			$prev_date = date( 'Y-m-d', strtotime( $data[ $header_map['Previous date'] ] ) );
			$next_date = date( 'Y-m-d', strtotime( $data[ $header_map['Next Date'] ] ) );

			// Sanitize amount.
			$amount = floatval( preg_replace( '/[^\d\.]/', '', $data[ $header_map['Amount'] ] ) );


			// 1. Check if the record already exists using UNIQUE KEY 'template_name' (Req #10).
			$existing_record = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT id, mainwp_site_id FROM {$table_name} WHERE template_name = %s", $template_name ) );

			$data_to_insert = array(
				'template_name'   => $template_name,
				'qb_client_name'  => $qb_client_name,
				'previous_date'   => $prev_date,
				'next_date'       => $next_date,
				'amount'          => $amount,
				'last_imported'   => $current_import_time,
			);
			$data_format = array(
				'%s', // template_name
				'%s', // qb_client_name
				'%s', // previous_date
				'%s', // next_date
				'%f', // amount
				'%d', // last_imported
			);

			if ( $existing_record ) {
				// Record exists, UPDATE it (Req #10 - Updates).
				$mainwp_site_id = (int) $existing_record->mainwp_site_id; // Explicit cast

				// Only if site is not mapped (ID is 0), attempt auto-mapping.
				if ( 0 === $mainwp_site_id ) {
					$site_id = (int) $this->attempt_auto_map( $qb_client_name, $site_names ); // Explicit cast
				} else {
					// Keep existing mapping (Req #5 - Manual overrides will be stored here).
					$site_id = $mainwp_site_id;
				}

				$data_to_insert['mainwp_site_id'] = $site_id;
				$data_format[] = '%d';

				$updated = $this->wpdb->update(
					$table_name,
					$data_to_insert,
					array( 'id' => $existing_record->id ),
					$data_format,
					array( '%d' )
				);

				if ( false === $updated ) {
					// Log a failure but continue import process
					MainWP_Billing_Utility::log_info( 'DB Error during UPDATE for template ' . $template_name . ': ' . $this->wpdb->last_error );
				} elseif ( $updated > 0 ) {
					$import_stats['updated']++;
				} else {
					$import_stats['skipped']++;
				}

			} else {
				// Record does NOT exist, INSERT it (Req #10 - Additions).

				// Auto-map the site name (Req #4).
				$site_id = (int) $this->attempt_auto_map( $qb_client_name, $site_names ); // Explicit cast

				$data_to_insert['mainwp_site_id'] = $site_id;
				$data_format[] = '%d';

				$inserted = $this->wpdb->insert(
					$table_name,
					$data_to_insert,
					$data_format
				);

				if ( $inserted ) {
					$import_stats['added']++;
				} else {
					// Log a failure but continue import process
					MainWP_Billing_Utility::log_info( 'DB Error during INSERT for template ' . $template_name . ': ' . $this->wpdb->last_error );
				}
			}
		}
		fclose( $handle );

		// 2. Removal Logic (Req #10 - Removals).
		if ( ! empty( $processed_template_names ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $processed_template_names ), '%s' ) );
			$deleted = $this->wpdb->query( $this->wpdb->prepare(
				"DELETE FROM {$table_name} WHERE template_name NOT IN ({$placeholders})",
				$processed_template_names
			) );

			if ( false === $deleted ) {
				// Log a failure but still return import stats
				MainWP_Billing_Utility::log_info( 'DB Error during DELETE: ' . $this->wpdb->last_error );
			} elseif ( $deleted > 0 ) {
				$import_stats['removed'] = intval( $deleted );
			}
		}

		return $import_stats;
	}


	/**
	 * Attempts to automatically map a QuickBooks client name to a MainWP site ID.
	 *
	 * @param string $qb_client_name The client name from QuickBooks.
	 * @param array  $site_names Array of lowercased site names/URLs mapped to site IDs.
	 *
	 * @return int The MainWP site ID or 0 if no match is found.
	 */
	private function attempt_auto_map( $qb_client_name, $site_names ) {
		$qb_name_lower = strtolower( $qb_client_name );

		// 1. Exact Name Match
		if ( isset( $site_names[ $qb_name_lower ] ) ) {
			return $site_names[ $qb_name_lower ];
		}

		// 2. Contains Match (e.g., "Client Name, Inc." matches "client name")
		foreach ( $site_names as $site_name_key => $site_id ) {
			if ( strpos( $qb_name_lower, $site_name_key ) !== false || strpos( $site_name_key, $qb_name_lower ) !== false ) {
				return $site_id;
			}
		}

		return 0; // No match found.
	}
}
