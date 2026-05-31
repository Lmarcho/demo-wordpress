<?php
/**
 * CreateCustomer.
 * php version 5.6
 *
 * @category CreateCustomer
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCart\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use FluentCart\App\Models\Customer;

/**
 * CreateCustomer
 *
 * @category CreateCustomer
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateCustomer extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'FluentCart';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'fluentcart_create_customer';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Customer', 'suretriggers' ),
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
	 * @return array|void
	 *
	 * @throws \Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! class_exists( '\FluentCart\App\Models\Customer' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCart is not installed or activated.', 'suretriggers' ),
			];
		}

		$email          = isset( $selected_options['email'] ) ? $selected_options['email'] : '';
		$first_name     = isset( $selected_options['first_name'] ) ? $selected_options['first_name'] : '';
		$last_name      = isset( $selected_options['last_name'] ) ? $selected_options['last_name'] : '';
		$address        = isset( $selected_options['address'] ) ? $selected_options['address'] : '';
		$city           = isset( $selected_options['city'] ) ? $selected_options['city'] : '';
		$state          = isset( $selected_options['state'] ) ? $selected_options['state'] : '';
		$zip_code       = isset( $selected_options['zip_code'] ) ? $selected_options['zip_code'] : '';
		$country        = isset( $selected_options['country'] ) ? $selected_options['country'] : '';
		$create_wp_user = isset( $selected_options['create_wordpress_user'] ) ? $selected_options['create_wordpress_user'] : 0;
		if ( empty( $email ) || ! is_email( $email ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Valid email address is required.', 'suretriggers' ),
			];
		}

		// Check if customer already exists.
		$existing_customer = Customer::where( 'email', $email )->first();
		if ( $existing_customer ) {
			return [
				'status'  => 'error',
				'message' => sprintf( __( 'Customer with email %s already exists.', 'suretriggers' ), $email ),
			];
		}

		$user_id_wp = null;
		
		// Create WordPress user if requested.
		if ( 1 == $create_wp_user ) {
			// Check if WordPress user with this email already exists.
			$existing_wp_user = get_user_by( 'email', $email );
			if ( $existing_wp_user ) {
				$user_id_wp = $existing_wp_user->ID;
			} else {
				// Create new WordPress user.
				$username = sanitize_user( $email );
				if ( username_exists( $username ) ) {
					$username = sanitize_user( $first_name . '_' . $last_name . '_' . time() );
				}
				
				$password   = wp_generate_password();
				$user_id_wp = wp_create_user( $username, $password, $email );
				
				if ( is_wp_error( $user_id_wp ) ) {
					return [
						'status'  => 'error',
						'message' => sprintf( __( 'Failed to create WordPress user: %s', 'suretriggers' ), $user_id_wp->get_error_message() ),
					];
				}
				
				// Update user meta with name.
				wp_update_user(
					[
						'ID'           => $user_id_wp,
						'first_name'   => $first_name,
						'last_name'    => $last_name,
						'display_name' => $first_name . ' ' . $last_name,
					] 
				);
			}
		}

		try {
			$customer_data = [
				'email'      => $email,
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'status'     => 'active',
				'country'    => $country,
				'city'       => $city,
				'state'      => $state,
				'postcode'   => $zip_code,
			];

			// Add user_id if WordPress user was created.
			if ( ! empty( $user_id_wp ) ) {
				$customer_data['user_id'] = $user_id_wp;
			}

			$customer = Customer::create( $customer_data );

			if ( ! $customer ) {
				return [
					'status'  => 'error',
					'message' => __( 'Failed to create customer.', 'suretriggers' ),
				];
			}

			// Fire FluentCart hook for customer creation.
			// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- FluentCart uses forward slashes in hook names
			do_action( 'fluent_cart/customer_created', $customer );

			$context = [
				'customer_id'            => $customer->id,
				'email'                  => $customer->email,
				'first_name'             => $customer->first_name,
				'last_name'              => $customer->last_name,
				'full_name'              => $customer->first_name . ' ' . $customer->last_name,
				'city'                   => $customer->city,
				'state'                  => $customer->state,
				'zip_code'               => $customer->postcode,
				'country'                => $customer->country,
				'status'                 => $customer->status,
				'user_id'                => $customer->user_id,
				'wordpress_user_created' => ! empty( $user_id_wp ) ? 1 : 0,
				'created_at'             => $customer->created_at,
			];

			return $context;

		} catch ( \Exception $e ) {
			return [
				'status'  => 'error',
				'message' => $e->getMessage(),
			];
		}
	}
}

CreateCustomer::get_instance();
