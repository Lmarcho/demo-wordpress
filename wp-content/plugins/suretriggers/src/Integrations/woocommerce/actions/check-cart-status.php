<?php
/**
 * CheckCartStatus.
 * php version 5.6
 *
 * @category CheckCartStatus
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WooCommerce\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Integrations\WooCommerce\WooCommerce;
use SureTriggers\Traits\SingletonLoader;
use WP_Error;

/**
 * CheckCartStatus
 *
 * @category CheckCartStatus
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CheckCartStatus extends AutomateAction {

	use SingletonLoader;

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'WooCommerce';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'wc_check_cart_status';

	/**
	 * Register the action.
	 *
	 * @param array $actions Actions array.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Check Cart Status', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];

		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id          User ID.
	 * @param int   $automation_id    Automation ID.
	 * @param array $fields           Fields.
	 * @param array $selected_options Selected options.
	 * @return array|WP_Error
	 * @throws Exception If WooCommerce functions are missing.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'WC' ) || ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error( 'woocommerce_not_available', __( 'WooCommerce is not available.', 'suretriggers' ) );
		}

		$user_identifier     = isset( $selected_options['user_identifier'] ) ? trim( $selected_options['user_identifier'] ) : '';
		$identifier_type     = isset( $selected_options['identifier_type'] ) ? $selected_options['identifier_type'] : 'user_id';
		$abandoned_threshold = isset( $selected_options['abandoned_threshold'] ) ? intval( $selected_options['abandoned_threshold'] ) : 60; // Minutes.

		// Validate identifier type.
		$valid_types = [ 'user_id', 'email', 'session_id' ];
		if ( ! in_array( $identifier_type, $valid_types, true ) ) {
			return new WP_Error( 'invalid_identifier_type', __( 'Invalid identifier type. Use "user_id", "email", or "session_id".', 'suretriggers' ) );
		}

		try {
			$cart_data = $this->get_cart_data( $user_identifier, $identifier_type );
			
			if ( is_wp_error( $cart_data ) ) {
				return $cart_data;
			}

			$cart_status = $this->determine_cart_status( $cart_data, $abandoned_threshold );
			
			$response_data = [
				'success'            => true,
				'cart_status'        => $cart_status['status'],
				'status_description' => $cart_status['description'],
				'cart_id'            => $cart_data['cart_id'],
				'user_id'            => $cart_data['user_id'],
				'user_email'         => $cart_data['user_email'],
				'cart_contents'      => $cart_data['contents'],
				'cart_total'         => $cart_data['total'],
				'cart_subtotal'      => $cart_data['subtotal'],
				'item_count'         => $cart_data['item_count'],
				'last_updated'       => $cart_data['last_updated'],
				'abandoned_duration' => $cart_status['abandoned_duration'],
				'recovery_info'      => $cart_status['recovery_info'],
				'cart_url'           => $this->get_cart_recovery_url( $cart_data ),
				'message'            => sprintf( __( 'Cart status: %s', 'suretriggers' ), $cart_status['description'] ),
			];

			return $response_data;

		} catch ( Exception $e ) {
			return new WP_Error( 'check_failed', sprintf( __( 'Failed to check cart status: %s', 'suretriggers' ), $e->getMessage() ) );
		}
	}

	/**
	 * Get cart data based on identifier.
	 *
	 * @param string $identifier Identifier value.
	 * @param string $type       Identifier type.
	 * @return array|WP_Error
	 */
	private function get_cart_data( $identifier, $type ) {
		global $wpdb;

		if ( empty( $identifier ) ) {
			return new WP_Error( 'missing_identifier', __( 'User identifier is required.', 'suretriggers' ) );
		}

		$cart_data  = null;
		$user_id    = 0;
		$user_email = '';

		switch ( $type ) {
			case 'user_id':
				$user_id = intval( $identifier );
				$user    = get_user_by( 'id', $user_id );
				if ( ! $user ) {
					return new WP_Error( 'user_not_found', __( 'User not found.', 'suretriggers' ) );
				}
				$user_email = $user->user_email;
				$cart_data  = $this->get_user_cart_data( $user_id );
				break;

			case 'email':
				$user = get_user_by( 'email', $identifier );
				if ( $user ) {
					$user_id    = $user->ID;
					$user_email = $user->user_email;
					$cart_data  = $this->get_user_cart_data( $user_id );
				} else {
					$user_email = $identifier;
					$cart_data  = $this->get_guest_cart_data( $identifier );
				}
				break;

			case 'session_id':
				$cart_data = $this->get_session_cart_data( $identifier );
				break;
		}

		if ( ! $cart_data ) {
			return new WP_Error( 'cart_not_found', __( 'No cart found for the specified identifier.', 'suretriggers' ) );
		}

		$cart_data['user_id']    = $user_id;
		$cart_data['user_email'] = $user_email;

		return $cart_data;
	}

	/**
	 * Get cart data for registered user.
	 *
	 * @param int $user_id User ID.
	 * @return array|null
	 */
	private function get_user_cart_data( $user_id ) {
		global $wpdb;

		// Get cart from user meta (persistent cart).
		$cart_meta = get_user_meta( $user_id, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );
		
		if ( empty( $cart_meta ) || ! is_array( $cart_meta ) || empty( $cart_meta['cart'] ) ) {
			// Try to get from sessions table.
			$session_data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT session_value, session_expiry FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s",
					$user_id
				)
			);

			if ( $session_data ) {
				$cart_data = maybe_unserialize( $session_data->session_value );
				if ( is_array( $cart_data ) && isset( $cart_data['cart'] ) ) {
					$cart_meta = [ 'cart' => $cart_data['cart'] ];
				}
			}
		}

		if ( empty( $cart_meta ) || ! is_array( $cart_meta ) || empty( $cart_meta['cart'] ) ) {
			return null;
		}

		return $this->format_cart_data( $cart_meta['cart'], "user_{$user_id}" );
	}

	/**
	 * Get cart data for guest user.
	 *
	 * @param string $email Guest email.
	 * @return array|null
	 */
	private function get_guest_cart_data( $email ) {
		global $wpdb;

		// Check if there's an abandoned cart plugin table.
		$table_name = $wpdb->prefix . 'ac_abandoned_cart_history_lite';
		
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {
			$abandoned_cart = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM ' . esc_sql( $table_name ) . ' WHERE email = %s ORDER BY time DESC LIMIT 1',
					$email
				)
			);

			if ( $abandoned_cart && ! empty( $abandoned_cart->abandoned_cart_info ) && isset( $abandoned_cart->time ) ) {
				$cart_info = json_decode( $abandoned_cart->abandoned_cart_info, true );
				if ( is_array( $cart_info ) ) {
					return $this->format_cart_data( $cart_info, "guest_{$email}", $abandoned_cart->time );
				}
			}
		}

		return null;
	}

	/**
	 * Get cart data by session ID.
	 *
	 * @param string $session_id Session ID.
	 * @return array|null
	 */
	private function get_session_cart_data( $session_id ) {
		global $wpdb;

		$session_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT session_value, session_expiry FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key = %s",
				$session_id
			)
		);

		if ( ! $session_data ) {
			return null;
		}

		$cart_data = maybe_unserialize( $session_data->session_value );
		
		if ( ! is_array( $cart_data ) || empty( $cart_data['cart'] ) ) {
			return null;
		}

		return $this->format_cart_data( $cart_data['cart'], $session_id, null, $session_data->session_expiry );
	}

	/**
	 * Format cart data into standardized format.
	 *
	 * @param array  $cart_contents Cart contents.
	 * @param string $cart_id       Cart identifier.
	 * @param string $timestamp     Last updated timestamp.
	 * @param int    $expiry        Session expiry.
	 * @return array
	 */
	private function format_cart_data( $cart_contents, $cart_id, $timestamp = null, $expiry = null ) {
		$total              = 0;
		$subtotal           = 0;
		$item_count         = 0;
		$formatted_contents = [];

		foreach ( $cart_contents as $cart_item_key => $cart_item ) {
			$product = wc_get_product( $cart_item['product_id'] );
			if ( ! $product ) {
				continue;
			}

			$item_total    = isset( $cart_item['line_total'] ) ? $cart_item['line_total'] : ( $product->get_price() * $cart_item['quantity'] );
			$item_subtotal = isset( $cart_item['line_subtotal'] ) ? $cart_item['line_subtotal'] : $item_total;

			$formatted_contents[] = [
				'product_id'    => $cart_item['product_id'],
				'product_name'  => $product->get_name(),
				'quantity'      => $cart_item['quantity'],
				'price'         => $product->get_price(),
				'line_total'    => $item_total,
				'line_subtotal' => $item_subtotal,
				'sku'           => $product->get_sku(),
			];

			$total      += $item_total;
			$subtotal   += $item_subtotal;
			$item_count += $cart_item['quantity'];
		}

		return [
			'cart_id'      => $cart_id,
			'contents'     => $formatted_contents,
			'total'        => wc_format_decimal( $total, 2 ),
			'subtotal'     => wc_format_decimal( $subtotal, 2 ),
			'item_count'   => $item_count,
			'last_updated' => $timestamp ? $timestamp : current_time( 'mysql' ),
			'expiry'       => $expiry,
		];
	}

	/**
	 * Determine cart status based on data and threshold.
	 *
	 * @param array $cart_data          Cart data.
	 * @param int   $abandoned_threshold Abandoned threshold in minutes.
	 * @return array
	 */
	private function determine_cart_status( $cart_data, $abandoned_threshold ) {
		$current_time      = time();
		$last_updated      = strtotime( $cart_data['last_updated'] );
		$time_diff_minutes = ( $current_time - $last_updated ) / 60;

		$status = [
			'status'             => 'active',
			'description'        => __( 'Cart is active', 'suretriggers' ),
			'abandoned_duration' => 0,
			'recovery_info'      => null,
		];

		// Check if cart is expired.
		if ( ! empty( $cart_data['expiry'] ) && $current_time > $cart_data['expiry'] ) {
			$status['status']             = 'expired';
			$status['description']        = __( 'Cart session has expired', 'suretriggers' );
			$status['abandoned_duration'] = $time_diff_minutes;
			return $status;
		}

		// Check if cart is abandoned.
		if ( $time_diff_minutes >= $abandoned_threshold ) {
			$status['status']             = 'abandoned';
			$status['description']        = sprintf( __( 'Cart has been abandoned for %d minutes', 'suretriggers' ), round( $time_diff_minutes ) );
			$status['abandoned_duration'] = $time_diff_minutes;

			// Check if there have been recovery attempts.
			$recovery_attempts = $this->get_recovery_attempts( $cart_data['cart_id'] );
			if ( ! empty( $recovery_attempts ) ) {
				$status['status']        = 'recovery_sent';
				$status['description']   = __( 'Cart is abandoned and recovery emails have been sent', 'suretriggers' );
				$status['recovery_info'] = $recovery_attempts;
			}
		}

		return $status;
	}

	/**
	 * Get recovery attempts for a cart.
	 *
	 * @param string $cart_id Cart ID.
	 * @return array
	 */
	private function get_recovery_attempts( $cart_id ) {
		return [];
	}

	/**
	 * Generate cart recovery URL.
	 *
	 * @param array $cart_data Cart data.
	 * @return string
	 */
	private function get_cart_recovery_url( $cart_data ) {
		$base_url = wc_get_cart_url();
		
		// Add recovery token if needed.
		$recovery_token = md5( $cart_data['cart_id'] . time() );
		
		return add_query_arg( 
			[ 
				'cart_recovery' => $recovery_token,
				'cart_id'       => base64_encode( $cart_data['cart_id'] ),
			], 
			$base_url 
		);
	}
}

CheckCartStatus::get_instance();
