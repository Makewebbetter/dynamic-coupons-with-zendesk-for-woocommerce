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
 * Manager File
 *
 * @link       https://makewebbbetter.com/
 * @since      1.0.0
 *
 * @package    zendesk-dynamic-coupons
 * @subpackage zendesk-dynamic-coupons/Library
 */
if ( ! class_exists( 'MWB_ZENCOUPON_MANAGER' ) ) {
	/**
	 * Manager File
	 *
	 * @link       https://makewebbbetter.com/
	 * @since      1.0.0
	 *
	 * @package    zendesk-dynamic-coupons
	 * @subpackage zendesk-dynamic-coupons/Library
	 */
	class MWB_ZENCOUPON_MANAGER {
		/**
		 * Initialize the class and set its object.
		 *
		 * @var $_instance
		 *
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

			$this->mwb_zencoupon_set_locale();
		}
		/**
		 * Defines the locale for this plugin for internationalization.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function mwb_zencoupon_set_locale() {

			$this->mwb_zencoupon_load_plugin_textdomain();
		}
		/**
		 * Loads the plugin text domain for translation.
		 *
		 * @since    1.0.0
		 */
		public function mwb_zencoupon_load_plugin_textdomain() {

			$var = load_plugin_textdomain(
				'dynamic-coupons-with-zendesk-for-woocommerce',
				false,
				dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
			);
		}
		/**
		 * Register routes for the order details class.
		 *
		 * @since    1.0.0
		 */
		public function mwb_zencoupon_register_routes() {

			register_rest_route(
				'zndskcoupon', '/zencoupons', array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'mwb_send_woo_coupons' ),
						'permission_callback' => 'mwb_zencoupon_get_items_permissions_check',
					),
				)
			);

			register_rest_route(
				'zndskcoupon', '/zencoupons/create_coupons', array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'mwb_generate_woo_coupon' ),
						'permission_callback' => 'mwb_zencoupon_get_items_permissions_check',
					),
				)
			);
		}
		/**
		 * Send zendesk client's details
		 *
		 * @since    1.0.0
		 */
		public function mwb_send_clients_details() {

			$status          = false;
			$suggestion_sent = get_option( 'zenwoo_coupons_suggestions_sent', false );
			if ( $suggestion_sent ) {
				$status = 'already-sent';
				return $status;
			}
			$email     = get_option( 'admin_email', '' );
			$admin     = get_user_by( 'email', $email );
			$admin_id  = $admin->ID;
			$firstname = get_user_meta( $admin_id, 'first_name', true );
			$lastname  = get_user_meta( $admin_id, 'last_name', true );
			$site_url  = ! empty( $admin->user_url ) ? $admin->user_url : '';
			$to        = sanitize_email( 'integrations@makewebbetter.com' );
			$subject   = "Zendesk Dynamic Coupons Customer's Details";
			$headers   = array( 'Content-Type: text/html; charset=UTF-8' );
			$message   = 'First Name:- ' . $firstname . '<br/>';
			$message  .= 'Last Name:- ' . $lastname . '<br/>';
			$message  .= 'Admin Email:- ' . $email . '<br/>';
			$message  .= 'Site Url:- ' . $site_url . '<br/>';
			$status    = wp_mail( $to, $subject, $message, $headers );
			return $status;
		}
		/**
		 * Sends woocommerce coupons to zendesk dashboard.
		 *
		 * @param array $request request parameters from zendesk.
		 * @return array $data coupon details data.
		 * @since    1.0.0
		 */
		public function mwb_send_woo_coupons( $request ) {

			global $woocommerce;

			$currency = get_option( 'woocommerce_currency' );
			$symbol   = html_entity_decode( get_woocommerce_currency_symbol( $currency ) );
			$key      = get_option( 'mwb_zendesk_coupon_key', '' );
			$no_coupon_found = "yes";

			$post_params = $request->get_params();

			if ( isset( $post_params ) && $key === $post_params['appKey'] ) {

				$coupons = get_posts(
					array(
						'posts_per_page' => -1,
						'post_type'      => 'shop_coupon',
						'post_status'    => 'publish',
					)
				);

				if ( ! empty( $coupons ) && isset( $coupons ) ) {

					foreach ( $coupons as $coupon ) {

						$amount          = get_post_meta( $coupon->ID, 'coupon_amount' );
						$disc_type       = get_post_meta( $coupon->ID, 'discount_type' );
						$currency_symbol = ( 'fixed_cart' === $disc_type[0] ) ? $symbol : '%';

						$coupon_obj = new WC_Coupon( $coupon->ID );

						if ( $coupon_obj->is_valid() ) {

							$data[] = array(

								'coupon_name'   => $coupon->post_title,
								'coupon_amt'    => $amount,
								'discount_type' => $currency_symbol,
								'msg_id'        => 2,
							);
							$no_coupon_found = "no";
						} 
					}
				} else {

					$data[] = array(
						'msg'    => __( 'No coupons found', 'dynamic-coupons-with-zendesk-for-woocommerce' ),
						'msg_id' => 0,
					);
				}

				$key_set = 1;
				update_option( 'mwb_zendesk_coupon_key_set', $key_set );

			} else {
				$data[] = array(
					'msg'    => __( 'Wrong Store URL', 'dynamic-coupons-with-zendesk-for-woocommerce' ),
					'msg_id' => 1,
				);
			}
			if($no_coupon_found == "yes") {
				$data[] = array(
					'msg'    => __( 'No coupons found', 'dynamic-coupons-with-zendesk-for-woocommerce' ),
					'msg_id' => 0,
				);
			}
			
			$data = wp_json_encode( $data );
			return $data;
		}
		/**
		 * Generates a new coupon for woocommerce store.
		 *
		 * @param array $request request parameters from zendesk.
		 * @return array $data coupon details data.
		 * @since    1.0.0
		 */
		public function mwb_generate_woo_coupon( $request ) {

			global $woocommerce;
			$post_params = $request->get_params();

			$currency        = get_option( 'woocommerce_currency' );
			$currency_symbol = html_entity_decode( get_woocommerce_currency_symbol( $currency ) );
			$key             = get_option( 'mwb_zendesk_coupon_key', '' );

			if ( isset( $post_params ) && $key === $post_params['appKey'] ) {

				$pre_name        = self::mwb_generate_random_string( 3 );
				$post_name       = self::mwb_generate_random_string();
				$coupon_code     = $pre_name . '-' . $post_name;
				$amount          = sanitize_text_field( ( ! empty( $post_params['amount'] ) ? $post_params['amount'] : '10' ) );
				$discount_type   = sanitize_text_field( ( ! empty( $post_params['discount-type'] ) ? $post_params['discount-type'] : 'fixed_cart' ) );
				$expiry_date     = sanitize_text_field( ( ! empty( $post_params['exp-date'] ) ? strtotime( $post_params['exp-date'] ) : '' ) );
				$currency_symbol = ( 'fixed_cart' === $discount_type ) ? $currency_symbol : '%';

				$coupon = array(
					'post_title'   => $coupon_code,
					'post_content' => '',
					'post_status'  => 'publish',
					'post_author'  => 1,
					'post_type'    => 'shop_coupon',
				);

				$new_coupon_id = wp_insert_post( $coupon );

				update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
				update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
				update_post_meta( $new_coupon_id, 'individual_use', 'no' );
				update_post_meta( $new_coupon_id, 'product_ids', '' );
				update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
				update_post_meta( $new_coupon_id, 'usage_limit', '1' );
				update_post_meta( $new_coupon_id, 'expiry_date', $expiry_date );
				update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
				update_post_meta( $new_coupon_id, 'free_shipping', 'no' );

				$data[] = array(

					'coupon_name'   => $coupon_code,
					'discount_type' => $currency_symbol,
					'amount'        => $amount,
				);
			} else {
				$data[] = array(
					'msg' => __( 'Wrong Store URL', 'dynamic-coupons-with-zendesk-for-woocommerce' ),
				);
			}

			$data = wp_json_encode( $data );
			return $data;
		}
		/**
		 * Generates a random string for coupon name.
		 *
		 * @param int $length length of the string.
		 * @return string $randomString random string.
		 * @since    1.0.0
		 */
		public function mwb_generate_random_string( $length = 5 ) {
			$characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$characterslength = strlen( $characters );
			$randomstring     = '';
			for ( $i = 0; $i < $length; $i++ ) {
				$randomstring .= $characters[ rand( 0, $characterslength - 1 ) ];
			}
			return $randomstring;
		}
	}
}
