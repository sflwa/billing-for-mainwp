<?php
/**
 * MainWP Billing Individual
 *
 * This class handles the Individual process.
 *
 * @package MainWP/Extensions
 */

 namespace MainWP\Extensions\Billing;

 /**
  * Class MainWP_Billing_Individual
  *
  * @package MainWP/Extensions
  */
class MainWP_Billing_Individual
{
	/**
	 * @var self|null The singleton instance of the class.
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return self|null
	 */
	public static function get_instance()
	{
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * MainWP_Billing_Individual constructor.
     *
     * @return void
	 */
	public function __construct()
	{
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
	}

	/**
	 * Admin init.
	 *
	 * @return void
	 */
	public function admin_init()
	{

	}

	/**
	 * Render individual page. (Req #8)
     *
     * @return void
	 */
	public function render_individual_page()
	{
		// Get the MainWP site ID from the URL parameter
		$site_id = isset( $_GET['id'] ) ? intval( wp_unslash( $_GET['id'] ) ) : 0;

		if ( empty( $site_id ) ) {
			return;
		}

		// Fetch all billing records mapped to this specific site ID
		$records = MainWP_Billing_DB::get_instance()->get_billing_records( array( 'mainwp_site_id' => $site_id ) );

		do_action( 'mainwp_pageheader_sites', 'BillingIndividual' );
		?>
        <div class="ui segment">
            <h2 class="ui header"><?php esc_html_e( 'Recurring Billing Information', 'mainwp-billing-extension' ); ?></h2>
			<div class="ui divider"></div>

            <?php if ( ! empty( $records ) ) : ?>
                <table class="ui celled unstackable table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'QuickBooks Client Name', 'mainwp-billing-extension' ); ?></th>
							<th><?php esc_html_e( 'Template Name', 'mainwp-billing-extension' ); ?></th>
							<th><?php esc_html_e( 'Amount', 'mainwp-billing-extension' ); ?></th>
							<th><?php esc_html_e( 'Next Billing Date', 'mainwp-billing-extension' ); ?></th>
							<th><?php esc_html_e( 'Previous Billing Date', 'mainwp-billing-extension' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $records as $record ) : ?>
							<tr>
								<td><?php echo esc_html( $record->qb_client_name ); ?></td>
								<td><?php echo esc_html( $record->template_name ); ?></td>
								<td><?php echo esc_html( '$' . number_format( floatval( $record->amount ), 2 ) ); ?></td>
								<td><?php echo MainWP_Billing_Utility::format_date( strtotime( $record->next_date ) ); ?></td>
								<td><?php echo MainWP_Billing_Utility::format_date( strtotime( $record->previous_date ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
            <?php else : ?>
                <div class="ui info message">
                    <div class="ui icon header">
                        <i class="money bill alternate outline icon"></i>
                        <?php esc_html_e( 'No Recurring Billing Found', 'mainwp-billing-extension' ); ?>
                    </div>
                    <p><?php esc_html_e( 'This site is not currently mapped to any recurring billing record. Please check the Billing Dashboard for unmapped records.', 'mainwp-billing-extension' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
		<?php
		do_action( 'mainwp_pagefooter_sites', 'BillingIndividual' );
	}
}
