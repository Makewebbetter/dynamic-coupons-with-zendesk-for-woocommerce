<?php
/**
 * Exit if accessed directly
 *
 * @package zendesk-dynamic-coupons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The api-specific functionality of the plugin.
 *
 * @link       https://makewebbetter.com/
 * @since      1.0.0
 *
 * @package    zendesk-dynamic-coupons
 */
require_once MWB_ZENCOUPON_DIR . '/Library/class-mwb-zencoupon-manager.php';
/**
 * The api-specific functionality of the plugin.
 *
 * @link       https://makewebbetter.com/
 * @since      1.0.0
 *
 * @package    zendesk-dynamic-coupons
 */
class Mwb_Zenwoocoupon_Api {
	/**
	 * Initialize the class and set its object.
	 *
	 * @var $_instance
	 * @since    1.0.0
	 */
	private static $_instance;
	/**
	 * Initialize the class and set its object.
	 *
	 * @since    1.0.0
	 */
	public static function get_instance() {

		self::$_instance = new self();
		if ( ! self::$_instance instanceof self ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
	/**
	 * Constructor of the class for fetching the endpoint.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->mwb_zencoupon_manager = MWB_ZENCOUPON_MANAGER::get_instance();
		add_action( 'wp_ajax_mwb_zenwoo_suggest_accept', array( $this, 'mwb_zenwoo_suggest_accept' ) );
		add_action( 'wp_ajax_mwb_zenwoo_suggest_later', array( $this, 'mwb_zenwoo_suggest_later' ) );
	}
	/**
	 * Registering routes.
	 *
	 * @since    1.0.0
	 */
	public function mwb_zencoupon_register_routes() {

		$this->mwb_zencoupon_manager->mwb_zencoupon_register_routes();
	}
	/**
	 * Save suggestion in DB
	 *
	 * @since    1.0.0
	 */
	public function mwb_zenwoo_suggest_later() {
		check_ajax_referer( 'zenwoo_security', 'zenwooSecurity' );
		update_option( 'zenwoo_coupons_suggestions_later', true );
		return true;
	}
	/**
	 * Check status of mail sent and save suggestion in DB
	 *
	 * @since    1.0.0
	 */
	public function mwb_zenwoo_suggest_accept() {
		check_ajax_referer( 'zenwoo_security', 'zenwooSecurity' );
		$status = $this->mwb_zencoupon_manager->mwb_send_clients_details();
		if ( $status && 'already-sent' !== $status ) {
			update_option( 'zenwoo_coupons_suggestions_sent', true );
			echo wp_json_encode( 'success' );
		} elseif ( 'already-sent' === $status ) {
			echo wp_json_encode( 'alreadySent' );
		} else {
			update_option( 'zenwoo_coupons_suggestions_later', true );
			echo wp_json_encode( 'failure' );
		}
		wp_die();
	}
}
