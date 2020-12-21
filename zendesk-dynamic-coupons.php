<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://makewebbetter.com/
 * @since             1.0.0
 * @package           zendesk-dynamic-coupons
 *
 * @wordpress-plugin
 * Plugin Name:       Dynamic Coupons with Zendesk for WooCommerce
 * Plugin URI:        https://wordpress.org/plugins/dynamic-coupons-with-zendesk-for-woocommerce
 * Description:       Connects WooCommerce store to Zendesk and sends WooCommerce coupon's code to use in your Zendesk instantly.
 * Version:           1.0.0
 * Author:            MakeWebBetter
 * Author URI:        https://makewebbetter.com/
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       zndskcoupon
 * Tested up to:      5.3
 * WC tested up to:   3.8.1
 * Domain Path:       /languages
 */

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$activated = true;
if ( function_exists( 'is_multisite' ) && is_multisite() ) {

	include_once ABSPATH . 'wp-admin/includes/plugin.php';

	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

		$activated = false;
	}
} else {

	if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

		$activated = false;
	}
}
/**
 * Check if WooCommerce is active
 */
if ( $activated ) {

	if ( ! defined( 'MWB_ZENCOUPON_PREFIX' ) ) {
		define( 'MWB_ZENCOUPON_PREFIX', 'mwb_zencoupon' );
	}

	if ( ! defined( 'MWB_ZENCOUPON_DIR' ) ) {
		define( 'MWB_ZENCOUPON_DIR', dirname( __FILE__ ) );
	}

	if ( ! defined( 'MWB_ZENCOUPON_DIR_URL' ) ) {
		define( 'MWB_ZENCOUPON_DIR_URL', plugin_dir_url( __FILE__ ) );
	}

	if ( ! defined( 'MWB_ZENCOUPON_DIR_PATH' ) ) {
		define( 'MWB_ZENCOUPON_DIR_PATH', plugin_dir_path( __FILE__ ) );
	}

	register_activation_hook( __FILE__, 'mwb_zencoupon_activation' );
	register_deactivation_hook( __FILE__, 'mwb_zencoupon_deactivation' );
	add_action( 'wp_loaded', 'mwb_zencoupon_activation' );
	/**
	 * Activation hook
	 *
	 * @since    1.0.0
	 */
	function mwb_zencoupon_activation() {

		$unique_key = get_option( 'mwb_zendesk_coupon_key' );
		if ( ! $unique_key ) {
			$unique_key = uniqid( 'zencp-key-', false );
			update_option( 'mwb_zendesk_coupon_key', $unique_key );
		}
	}
	/**
	 * Deactivation hook
	 *
	 * @since    1.0.0
	 */
	function mwb_zencoupon_deactivation() {
		update_option( 'mwb_zendesk_coupon_key_set', 0 );
	}
	/**
	 * Permission check
	 *
	 * @param string $request permission request.
	 * @return boolean true/false
	 * @since    1.0.0
	 */
	function mwb_zencoupon_get_items_permissions_check( $request ) {

		return true;
	}
	/**
	 * Adds api file for the plugin.
	 *
	 * @since    1.0.0
	 */
	function mwb_zencoupon_add_api_file_for_plugin() {

		// including supporting file of plugin.
		include_once MWB_ZENCOUPON_DIR . '/class-mwb-zenwoocoupon-api.php';
		$mwb_zndsk_instance = Mwb_Zenwoocoupon_Api::get_instance();

		add_action( 'rest_api_init', array( $mwb_zndsk_instance, 'mwb_zencoupon_register_routes' ) );
	}

	add_action( 'plugins_loaded', 'mwb_zencoupon_add_api_file_for_plugin' );
	/**
	 * Enqueue scripts and styles
	 *
	 * @since    1.0.0
	 */
	function mwb_zenwoo_coupons_enqueue_script() {

		wp_register_style( 'zenwoo_scripts', MWB_ZENCOUPON_DIR_URL . 'assets/zenwoo-admin.css', false, '1.0', 'all' );
		wp_enqueue_style( 'zenwoo_scripts' );
		wp_register_script( 'zenwoo_scripts', MWB_ZENCOUPON_DIR_URL . 'assets/zenwoo-admin.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_script( 'zenwoo_scripts' );
		wp_localize_script(
			'zenwoo_scripts', 'zenwoo_ajax_object',
			array(
				'ajax_url'              => admin_url( 'admin-ajax.php' ),
				'zenwooSecurity'        => wp_create_nonce( 'zenwoo_security' ),
				'zenwooMailSuccess'     => __( 'Mail Sent Successfully.', 'zndskcoupon' ),
				'zenwooMailFailure'     => __( 'Mail not sent', 'zndskcoupon' ),
				'zenwooMailAlreadySent' => __( 'Mail already sent', 'zndskcoupon' ),
			)
		);
	}
	add_action( 'admin_init', 'mwb_zenwoo_coupons_enqueue_script' );
	/**
	 * Show plugin development notice
	 *
	 * @since    1.0.0
	 */
	function mwb_zenwoo_coupons_admin_notice__success() {

		$suggest_sent    = get_option( 'zenwoo_coupons_suggestions_sent', '' );
		$suggest_ignored = get_option( 'zenwoo_coupons_suggestions_later', '' );
		$coupon_key_set  = get_option( 'mwb_zendesk_coupon_key_set', '' );
		$coupon_key      = get_option( 'mwb_zendesk_coupon_key', '' );
		$plugin_name     = 'Dynamic Coupons with Zendesk for WooCommerce';
		?>
		<div class="notice notice-success mwb-zndsk-form-div" style="<?php echo ( '1' === $suggest_sent || '1' === $suggest_ignored ) ? 'display: none;' : 'display: block;'; ?>">
			<p><?php /* translators: %2$2s Plugin name */ echo sprintf( esc_html__( 'Support the %1$1s%2$2s%3$3s plugin development by sending us tracking data( we just want your Email Address and Name that too only once ).', 'zndskcoupon' ), '<strong>', esc_html( $plugin_name ), '</strong>' ); ?></p>
			<input type="button" class="button button-primary mwb-coupon-accept-button" name="mwb_accept_button" value="Accept">
			<input type="button" class="button mwb-coupon-reject-button" name="mwb_reject_button" value="Ignore">
		</div>
		<div class="notice notice-success mwb-zndsk-coupon-key" style="<?php echo ( '1' === $coupon_key_set ) ? 'display: none;' : 'display: block;'; ?>">
		<p><?php /* translators: %2$2s Dynamic coupon id %4$4s Plugin name */ echo sprintf( esc_html__( 'Your %1$1s%4$4s%3$3s key is %1$1s%2$2s%3$3s. Enter this key while installing the app.', 'zndskcoupon' ), '<strong>', esc_html( $coupon_key ), '</strong>', esc_html( $plugin_name ) ); ?></p>
		</div>
		<div style="display: none;" class="mwb_loading-style-bg" id="mwb_zndsk_loader">
			<img src="<?php echo esc_url( MWB_ZENCOUPON_DIR_URL . 'assets/images/loader.gif' ); ?>">
		</div>
		<?php
	}
	add_action( 'admin_notices', 'mwb_zenwoo_coupons_admin_notice__success' );
} else {
	/**
	 * Error notice
	 *
	 * @since    1.0.0
	 */
	function mwb_zencoupon_plugin_error_notice() {
		update_option( 'mwb_zendesk_coupon_key_set', 0 );
		?>
			<div class="error notice is-dismissible">
			<p><?php esc_html_e( 'Woocommerce is not activated, please activate woocommerce first to install and use zendesk woocommerce plugin.', 'zndskcoupon' ); ?></p>
			</div>
			<style>
			#message{display:none;}
			</style>
			<?php
	}

	add_action( 'admin_init', 'mwb_zencoupon_plugin_deactivate' );
	/**
	 * Error hook
	 *
	 * @since    1.0.0
	 */
	function mwb_zencoupon_plugin_deactivate() {

		deactivate_plugins( plugin_basename( __FILE__ ) );

		global $wp_rewrite;
		$wp_rewrite->flush_rules();

		add_action( 'admin_notices', 'mwb_zencoupon_plugin_error_notice' );
	}
}
