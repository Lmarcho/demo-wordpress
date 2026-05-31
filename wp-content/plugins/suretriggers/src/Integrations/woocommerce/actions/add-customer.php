<?php
/**
 * AddNewCustomer.
 * php version 5.6
 *
 * @category AddNewCustomer
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Woocommerce\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use WP_Error;
use WC_Customer;

/**
 * AddNewCustomer
 *
 * @category AddNewCustomer
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class AddNewCustomer extends AutomateAction {

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
	public $action = 'wc_add_new_customer';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Add New Customer in WooCommerce', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];

		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id user_id.
	 * @param int   $automation_id automation_id.
	 * @param array $fields fields.
	 * @param array $selected_options selectedOptions.
	 *
	 * @return object|array|void
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'wc_create_new_customer' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WooCommerce function wc_create_new_customer not found.', 'suretriggers' ), 
				
			];
		}

		if ( ! class_exists( 'WC_Customer' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WooCommerce class WC_Customer not found.', 'suretriggers' ), 
				
			];
		}
		
		$email    = $selected_options['email'];
		$password = isset( $selected_options['password'] ) ? $selected_options['password'] : wp_generate_password();
		$username = isset( $selected_options['username'] ) ? $selected_options['username'] : sanitize_user( current( explode( '@', $email ) ), true );

		if ( email_exists( $email ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Email already exists.', 'suretriggers' ), 
				
			];
		}

		$user_args = [];
		if ( isset( $selected_options['first_name'] ) ) {
			$user_args['first_name'] = $selected_options['first_name'];
		}
		if ( isset( $selected_options['last_name'] ) ) {
			$user_args['last_name'] = $selected_options['last_name'];
		}
		if ( isset( $selected_options['display_name'] ) ) {
			$user_args['display_name'] = $selected_options['display_name'];
		}

		$user_id = wc_create_new_customer( $email, $username, $password, $user_args );

		if ( is_wp_error( $user_id ) ) {
			return [
				'status'  => 'error',
				'message' => $user_id->get_error_message(),
			];
		}

		$customer = new WC_Customer( $user_id );

		$customer->set_first_name( isset( $selected_options['first_name'] ) ? $selected_options['first_name'] : '' );
		$customer->set_last_name( isset( $selected_options['last_name'] ) ? $selected_options['last_name'] : '' );
		$customer->set_display_name( isset( $selected_options['display_name'] ) ? $selected_options['display_name'] : $username );
		$customer->set_email( $email );
		$customer->set_billing_email( isset( $selected_options['billing_email'] ) ? $selected_options['billing_email'] : $email );

		// Set billing fields.
		$billing_keys = [ 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country', 'state', 'phone' ];
		foreach ( $billing_keys as $key ) {
			$value  = isset( $selected_options[ "billing_$key" ] ) ? $selected_options[ "billing_$key" ] : '';
			$setter = "set_billing_$key";
			$customer->$setter( $value );
		}

		// Set shipping fields.
		$shipping_keys = [ 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'postcode', 'country', 'state', 'phone' ];
		foreach ( $shipping_keys as $key ) {
			$value  = isset( $selected_options[ "shipping_$key" ] ) ? $selected_options[ "shipping_$key" ] : '';
			$setter = "set_shipping_$key";
			$customer->$setter( $value );
		}

		$customer->save();

		return [
			'customer' => $customer->get_data(),
		];
	}
}

AddNewCustomer::get_instance();
