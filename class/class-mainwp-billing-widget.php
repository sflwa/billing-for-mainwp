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
		$all_sites = MainWP_Billing_Utility::get_websites();
		$mapped_site_ids = MainWP_Billing_DB::get_instance()->get_all_mapped_site_ids();

		$unmapped_sites = array();

		foreach ( $all_sites as $site ) {
			if ( ! in_array( intval( $site->id ), $mapped_site_ids ) ) {
				$unmapped_sites[] = $site;
			}
		}

		$unmapped_count = count( $unmapped_sites );

		?>
		<div class="ui grid">
			<div class="twelve wide column">
				<h3 class="ui header handle-drag">
					<?php esc_html_e( 'Missing Recurring Billing', 'mainwp-billing-extension' ); ?>
					<div class="sub header"><?php esc_html_e( 'Sites missing an assigned recurring billing record.', 'mainwp-billing-extension' ); ?></div>
				</h3>
			</div>
		</div>
		<div class="ui hidden divider"></div>
        <div class="ui fluid segment">
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
		<div class="ui hidden divider"></div>
		<div class="ui divider" style="margin-left:-1em;margin-right:-1em;"></div>
		<div class="ui two columns grid">
			<div class="left aligned column">
				<a href="admin.php?page=Extensions-Billing-For-Mainwp-Main" class="ui basic green button"><?php esc_html_e( 'Billing Dashboard', 'mainwp-billing-extension' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Individual Metabox
	 *
	 * Renders the individual site Overview page widget content.
	 */
	public static function render_site_overview_widget() {
		$site_id = isset( $_GET['dashboard'] ) ? $_GET['dashboard'] : 0;

		if ( empty( $site_id ) ) {
			return;
		}
		?>
        <div class="ui grid">
            <div class="twelve wide column">
                <h3 class="ui header handle-drag">
					<?php echo __( 'Billing Individual Widget', 'mainwp-billing-extension' ); ?>
                    <div class="sub header"><?php echo __( 'This is the Billing Individual Widget.', 'mainwp-billing-extension' ); ?></div>
                </h3>
            </div>
        </div>
        <div class="ui hidden divider"></div>
        <div class="ui fluid placeholder">
            <div class="image header">
                <div class="line"></div>
                <div class="line"></div>
            </div>
        </div>
        <div class="ui hidden divider"></div>
        <div class="ui divider" style="margin-left:-1em;margin-right:-1em;"></div>
        <div class="ui two columns grid">
            <div class="left aligned column">
                <a href="admin.php?page=Extensions-Billing-For-Mainwp-Main" class="ui basic green button"><?php esc_html_e( 'Billing Dashboard', 'mainwp-billing-extension' ); ?></a>
            </div>
        </div>
		<?php
	}
}
