<?php
/**
 * MainWP Billing Widget
 *
 * This class handles the Widget process.
 *
 * @package MainWP/Extensions
 */

 namespace MainWP\Extensions\Billing;

 /**
  * Class MainWP_Billing_Widget
  *
  * @package MainWP/Extensions
  */
class MainWP_Billing_Widget {

	private static $instance = null;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		// construct.
	}


	/**
	 * Render Metabox
	 *
	 * Initiates the correct widget depending on which page the user lands on.
	 */
	public function render_metabox() {
		if ( ! isset( $_GET['page'] ) || 'managesites' == $_GET['page'] ) {
			$this->render_site_overview_widget();
		} else {
			$this->render_general_overview_widget();
		}
	}

	/**
	 * Global Metabox
	 *
	 * Renders the Overview page widget content. Displays sites with no recurring billing. (Req #7)
	 */
public function render_general_overview_widget() {
		$utility = MainWP_Billing_Utility::get_instance();
		$exclusions = $utility->get_exclusion_settings();
		$excluded_clients = $exclusions['excluded_client_ids'];
		$excluded_sites   = $exclusions['excluded_site_ids'];

		// Fetch all sites with their mapping status (0 means no client filter)
		$all_sites_with_status = MainWP_Billing_DB::get_instance()->get_all_mainwp_sites_with_mapping_status( 0 );

		$unmapped_sites = array();
		$total_sites = count( MainWP_Billing_Utility::get_websites() ); // Total sites before exclusion for the sub header count

		foreach ( $all_sites_with_status as $site ) {
			// Apply exclusions filter first, matching the dashboard logic
			if ( in_array( intval( $site->client_id ), $excluded_clients ) || in_array( intval( $site->id ), $excluded_sites ) ) {
				// Skip excluded sites/clients
				continue;
			}

			// Check for unmapped status (is_mapped = 0)
			if ( 0 === intval( $site->is_mapped ) ) {
				$unmapped_sites[] = $site;
			}
		}

		$unmapped_count = count( $unmapped_sites );
		
		// New Header structure
		?>
		<div class="mainwp-widget-header">
			<div class="ui grid">
				<div class="twelve wide column">
					<h2 class="ui header handle-drag">
						<?php esc_html_e( 'Missing Recurring Billing', 'mainwp-billing-extension' ); ?>
						<div class="sub header">
							<?php
							echo sprintf( esc_html__( 'Displaying %d unmapped sites across all sites (total sites: %d).', 'mainwp-billing-extension' ),
								$unmapped_count,
								$total_sites
							);
							?>
						</div>
					</h2>
				</div>
				<div class="four wide column right aligned">
					<a href="admin.php?page=Extensions-Billing-For-Mainwp-Main&tab=mapping" class="ui button mini green" style="margin-top: 5px;">
						<i class="exchange icon"></i>
						<?php esc_html_e( 'Go to Mapping', 'mainwp-billing-extension' ); ?>
					</a>
				</div>
			</div>
		</div>
        <div class="ui hidden divider"></div>
        <div class="ui fluid segment" style="overflow-x: auto;">
			<?php if ( $unmapped_count > 0 ) : ?>
                <div class="ui red message">
					<?php echo sprintf( esc_html( _n( 'There is %d site without recurring billing.', 'There are %d sites without recurring billing.', $unmapped_count, 'mainwp-billing-extension' ) ), $unmapped_count ); ?>
                </div>
                <table class="ui unstackable table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Site Name', 'mainwp-billing-extension' ); ?></th>
                            <th><?php esc_html_e( 'URL', 'mainwp-billing-extension' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
						<?php foreach ( $unmapped_sites as $site ) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url( 'admin.php?page=managesites&dashboard=' . $site->id ); ?>"><?php echo esc_html( $site->name ); ?></a>
                                </td>
                                <td><?php echo esc_html( MainWP_Billing_Utility::get_nice_url( $site->url ) ); ?></td>
                            </tr>
						<?php endforeach; ?>
                    </tbody>
                </table>
			<?php else : ?>
                <div class="ui green message">
					<?php esc_html_e( 'All MainWP sites have an associated recurring billing record.', 'mainwp-billing-extension' ); ?>
                </div>
			<?php endif; ?>
        </div>
		
		
		<?php
	}

	/**
	 * Individual Metabox
	 *
	 * Renders the individual site Overview page widget content.
	 */
	public static function render_site_overview_widget() {
		$site_id = isset( $_GET['dashboard'] ) ? intval( wp_unslash( $_GET['dashboard'] ) ) : 0;

		if ( empty( $site_id ) ) {
			return;
		}

		// Fetch site data to get client ID
		$site_data = MainWP_Billing_Utility::get_websites( $site_id ); //
		$site = ( ! empty( $site_data ) ) ? $site_data[0] : null;

		if ( null === $site ) {
			return; // Site not found in DB
		}
		
		// Fetch billing records mapped to this specific site ID
		$records = MainWP_Billing_DB::get_instance()->get_billing_records( array( 'mainwp_site_id' => $site_id ) ); //
		$records_count = count( $records );

		// Check exclusion status
		$utility = MainWP_Billing_Utility::get_instance();
		$exclusions = $utility->get_exclusion_settings(); //
		$excluded_clients = $exclusions['excluded_client_ids'];
		$excluded_sites   = $exclusions['excluded_site_ids'];

		$is_excluded = in_array( $site_id, $excluded_sites ) || ( isset( $site->client_id ) && in_array( intval( $site->client_id ), $excluded_clients ) );

		?>
        <div class="mainwp-widget-header">
            <div class="ui grid">
                <div class="twelve wide column">
                    <h2 class="ui header handle-drag">
                        <?php esc_html_e( 'Recurring Billing Status', 'mainwp-billing-extension' ); ?>
                        <div class="sub header">
							<?php
							if ( $is_excluded ) {
								esc_html_e( 'This site is excluded from billing reports.', 'mainwp-billing-extension' );
							} else {
								echo sprintf( esc_html( _n( 'Found %d mapped billing record.', 'Found %d mapped billing records.', $records_count, 'mainwp-billing-extension' ) ), $records_count );
							}
							?>
                        </div>
                    </h2>
                </div>
                <div class="four wide column right aligned">
                    
                </div>
            </div>
        </div>
        <div class="ui hidden divider"></div>
        <div class="ui fluid segment" style="overflow-x: auto;">
			<?php
			if ( ! empty( $records ) ) :
				// 2) Show any mapped billing items
				?>
                <table class="ui celled unstackable table">
                    <thead>
                    <tr>
                        <th><?php esc_html_e( 'Template', 'mainwp-billing-extension' ); ?></th>
                        <th><?php esc_html_e( 'Amount', 'mainwp-billing-extension' ); ?></th>
                        <th><?php esc_html_e( 'Next Date', 'mainwp-billing-extension' ); ?></th>
                    </tr>
                    </thead>
                    <tbody>
					<?php foreach ( $records as $record ) : ?>
                        <tr>
                            <td><?php echo esc_html( $record->template_name ); ?></td>
                            <td><?php echo esc_html( '$' . number_format( floatval( $record->amount ), 2 ) ); ?></td>
                            <td><?php echo MainWP_Billing_Utility::format_date( strtotime( $record->next_date ) ); ?></td>
                        </tr>
					<?php endforeach; ?>
                    </tbody>
                </table>
			<?php elseif ( $is_excluded ) : ?>
                <div class="ui green message">
                    <p style="font-weight: bold;">
						<?php esc_html_e( 'This site is explicitly excluded from all billing reports.', 'mainwp-billing-extension' ); ?>
                    </p>
                </div>
			<?php else : ?>
                <div class="ui red message">
                    <p style="font-weight: bold;">
						<?php esc_html_e( 'WARNING: No recurring billing is configured for this site.', 'mainwp-billing-extension' ); ?>
                    </p>
                    <a href="admin.php?page=Extensions-Billing-For-Mainwp-Main&tab=mapping" class="ui basic red button">
						<?php esc_html_e( 'Go to Mapping Page', 'mainwp-billing-extension' ); ?>
                    </a>
                </div>
			<?php endif; ?>
        </div>
      
		<?php
	}
}
