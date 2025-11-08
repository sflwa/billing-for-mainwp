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
	 * Renders the Overview page widget content.
	 */
	public function render_general_overview_widget() {
		?>
		<div class="ui grid">
			<div class="twelve wide column">
				<h3 class="ui header handle-drag">
					<?php esc_html_e( 'Billing Overview Widget', 'mainwp-billing-extension' ); ?>
					<div class="sub header"><?php esc_html_e( 'This is the Billing Overview Widget.', 'mainwp-billing-extension' ); ?></div>
				</h3>
			</div>
		</div>
		<div class="ui hidden divider"></div>
        <div class="ui fluid placeholder">
            <div class="image header">
                <div class="line"></div>
                <div class="line"></div>
            </div>
            <div class="paragraph">
                <div class="line"></div>
                <div class="line"></div>
                <div class="line"></div>
            </div>
        </div>
		<div class="ui hidden divider"></div>
		<div class="ui divider" style="margin-left:-1em;margin-right:-1em;"></div>
		<div class="ui two columns grid">
			<div class="left aligned column">
				<a href="admin.php?page=Extensions-Mainwp-Billing-Extension" class="ui basic green button"><?php esc_html_e( 'Billing Dashboard', 'mainwp-billing-extension' ); ?></a>
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
                <a href="admin.php?page=Extensions-Mainwp-Billing-Extension" class="ui basic green button"><?php esc_html_e( 'Billing Dashboard', 'mainwp-billing-extension' ); ?></a>
            </div>
        </div>
		<?php
	}
}
