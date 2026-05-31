<?php
/**
 * AddUpdateProfilepressCustomer.
 * php version 5.6
 *
 * @category AddUpdateProfilepressCustomer
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.1.5
 */

namespace SureTriggers\Integrations\ProfilePress\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use ProfilePress\Core\Membership\Models\Customer\CustomerFactory;
use ProfilePress\Core\Membership\Models\Customer\CustomerEntity;


/**
 * AddUpdateProfilepressCustomer
 *
 * @category AddUpdateProfilepressCustomer
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class AddUpdateProfilepressCustomer extends AutomateAction {


	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'ProfilePress';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'add_update_profilepress_customer';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Add/Update ProfilePress customer', 'suretriggers' ),
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
	 * @return array
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {

		$email      = sanitize_email( $selected_options['user_email'] );
		$user_name  = sanitize_text_field( $selected_options['user_name'] );
		$first_name = isset( $selected_options['first_name'] ) ? sanitize_text_field( $selected_options['first_name'] ) : '';
		$last_name  = isset( $selected_options['last_name'] ) ? sanitize_text_field( $selected_options['last_name'] ) : '';

		/**
		 * User Data
		 */
		$user_pass = empty( $selected_options['password'] ) ? wp_generate_password() : $selected_options['password'];

		$userdata = [
			'user_login' => $user_name,
			'user_email' => $email,
			'user_pass'  => $user_pass,
			'first_name' => $first_name,
			'last_name'  => $last_name,
		];

		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			$user = get_user_by( 'login', $user_name );
		}
		

		if ( $user ) {
			$user_id        = $user->ID;
			$userdata['ID'] = $user_id;

			/**
			 * Skipping if empty value.
			 */
			if ( empty( $userdata['user_login'] ) ) {
				unset( $userdata['user_login'] );
			}
			if ( empty( $userdata['first_name'] ) ) {
				unset( $userdata['first_name'] );
			}
			if ( empty( $userdata['last_name'] ) ) {
				unset( $userdata['last_name'] );
			}
			if ( empty( $selected_options['password'] ) ) {
				unset( $userdata['user_pass'] );
			}
			
			wp_update_user( wp_slash( $userdata ) );
		} else {
			$user_id = wp_insert_user( wp_slash( $userdata ) );
		}

		if ( ! is_wp_error( $user_id ) ) {

			$billing_keys = [ 'address', 'city', 'country', 'state', 'postcode', 'phone' ];
			foreach ( $billing_keys as $key ) {
				$post_key = "billing_$key";
				$value    = isset( $selected_options[ $post_key ] ) ? sanitize_textarea_field( $selected_options[ $post_key ] ) : '';
				$setter   = 'ppress_billing_' . $key;
				update_user_meta( $user_id, $setter, $value );
			}

			if ( class_exists( 'ProfilePress\Core\Membership\Models\Customer\CustomerFactory' ) ) {
				$customer = CustomerFactory::fromUserId( $user_id );
				if ( ! $customer->exists() ) {
					if ( class_exists( 'ProfilePress\Core\Membership\Models\Customer\CustomerEntity' ) ) {
						$new_customer          = new CustomerEntity();
						$new_customer->user_id = $user_id;
						$customer_id           = $new_customer->save();

						if ( empty( $customer_id ) ) {
							return [
								'status'   => __( 'Error', 'suretriggers' ),
								'response' => __( 'Error creating customer record.', 'suretriggers' ), 
								
							];
						} else {
							$customer_details = CustomerFactory::fromId( absint( $customer_id ) );
							return [
								'status'   => __( 'Success', 'suretriggers' ),
								'response' => __( 'Customer successfully created and linked to user.', 'suretriggers' ),
								'customer' => (array) $customer_details,
							];
						}
					}
				} else {
					return [
						'status'   => __( 'Success', 'suretriggers' ),
						'response' => __( 'Customer successfully updated.', 'suretriggers' ),
						'customer' => (array) $customer,
					];
				}
			}
		}

		return [
			'status'   => __( 'Error', 'suretriggers' ),
			'response' => __( 'No customer has been created or updated', 'suretriggers' ), 
			
		];
	}
}

AddUpdateProfilepressCustomer::get_instance();
