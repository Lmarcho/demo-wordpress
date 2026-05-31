<?php
/**
 * GetCustomer.
 * php version 5.6
 *
 * @category GetCustomer
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
 * GetCustomer
 *
 * @category GetCustomer
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetCustomer extends AutomateAction {

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
	public $action = 'fluentcart_get_customer';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get Customer', 'suretriggers' ),
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

		$customer_id    = isset( $selected_options['customer_id'] ) ? $selected_options['customer_id'] : '';
		$customer_email = isset( $selected_options['customer_email'] ) ? $selected_options['customer_email'] : '';
		$user_id_wp     = isset( $selected_options['user_id'] ) ? $selected_options['user_id'] : '';
		
		if ( empty( $customer_id ) && empty( $customer_email ) && empty( $user_id_wp ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Customer ID, email, or WordPress user ID is required.', 'suretriggers' ),
			];
		}

		try {
			$customer = null;

			// Find customer by ID first (most specific).
			if ( ! empty( $customer_id ) ) {
				$customer = Customer::find( $customer_id );
			} elseif ( ! empty( $customer_email ) && is_email( $customer_email ) ) {
				// Then by email.
				$customer = Customer::where( 'email', $customer_email )->first();
			} elseif ( ! empty( $user_id_wp ) ) {
				// Finally by WordPress user ID.
				$customer = Customer::where( 'user_id', $user_id_wp )->first();
			}

			if ( ! $customer ) {
				return [
					'status'  => 'error',
					'message' => __( 'Customer not found.', 'suretriggers' ),
				];
			}

			$context = [
				'customer_id'         => $customer->id,
				'email'               => $customer->email,
				'first_name'          => $customer->first_name,
				'last_name'           => $customer->last_name,
				'full_name'           => $customer->first_name . ' ' . $customer->last_name,
				'status'              => $customer->status,
				'country'             => $customer->country,
				'city'                => $customer->city,
				'state'               => $customer->state,
				'postcode'            => $customer->postcode,
				'notes'               => $customer->notes,
				'uuid'                => $customer->uuid,
				'user_id'             => $customer->user_id,
				'contact_id'          => $customer->contact_id,
				'purchase_count'      => $customer->purchase_count,
				'purchase_value'      => $customer->purchase_value,
				'ltv'                 => $customer->ltv,
				'aov'                 => $customer->aov,
				'first_purchase_date' => $customer->first_purchase_date,
				'last_purchase_date'  => $customer->last_purchase_date,
				'created_at'          => $customer->created_at,
				'updated_at'          => $customer->updated_at,
			];

			// Add WordPress user information if linked.
			if ( $customer->user_id ) {
				$wp_user = get_user_by( 'ID', $customer->user_id );
				if ( $wp_user ) {
					$context['wp_user_login']        = $wp_user->user_login;
					$context['wp_user_display_name'] = $wp_user->display_name;
					$context['wp_user_registered']   = $wp_user->user_registered;
					$context['wp_user_roles']        = implode( ', ', $wp_user->roles );
				}
			}


			return $context;

		} catch ( \Exception $e ) {
			return [
				'status'  => 'error',
				'message' => $e->getMessage(),
			];
		}
	}
}

GetCustomer::get_instance();
