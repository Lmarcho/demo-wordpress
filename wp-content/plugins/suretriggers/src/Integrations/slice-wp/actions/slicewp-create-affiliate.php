<?php
/**
 * SliceWPCreateAffiliate.
 * php version 5.6
 *
 * @category SliceWPCreateAffiliate
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\SliceWP\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use Exception;

/**
 * SliceWPCreateAffiliate
 *
 * @category SliceWPCreateAffiliate
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class SliceWPCreateAffiliate extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'SliceWP';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'slicewp_create_affiliate';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Affiliate', 'suretriggers' ),
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
	 * @psalm-suppress UndefinedMethod
	 * @throws Exception Exception.
	 * 
	 * @return array|bool|void
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		
	
		if ( ! function_exists( 'slicewp_insert_affiliate' ) ) {
			return [
				'status'  => 'error',
				'message' => 'SliceWP functions not found.',
			];
		}

		// Get user by email.
		$wp_user = get_user_by( 'email', $selected_options['user_login'] );
		if ( ! $wp_user ) {
			return [
				'status'  => 'error',
				'message' => 'User not found. Please provide a valid user email.',
			];
		}
		
		$userid = $wp_user->data->ID;
		
		// Check if user is already an affiliate.
		if ( function_exists( 'slicewp_get_affiliate_by_user_id' ) ) {
			$existing_affiliate = slicewp_get_affiliate_by_user_id( $userid );
			if ( $existing_affiliate ) {
				return [
					'status'  => 'error',
					'message' => 'User is already an affiliate.',
				];
			}
		}

		// Prepare affiliate data exactly like SliceWP implementation.
		$affiliate                  = [];
		$affiliate['user_id']       = absint( $userid );
		$affiliate['date_created']  = ! empty( $selected_options['affiliate_date'] ) ? $selected_options['affiliate_date'] : ( function_exists( 'slicewp_mysql_gmdate' ) ? slicewp_mysql_gmdate() : current_time( 'mysql', true ) );
		$affiliate['date_modified'] = $affiliate['date_created'];
		$affiliate['payment_email'] = ! empty( $selected_options['payment_email'] ) ? sanitize_email( $selected_options['payment_email'] ) : '';
		$affiliate['website']       = ! empty( $selected_options['website'] ) ? esc_url( $selected_options['website'] ) : '';
		$affiliate['status']        = ! empty( $selected_options['status'] ) ? sanitize_text_field( $selected_options['status'] ) : 'active';
		
		// Handle welcome email flag for backward compatibility - handle both string and integer inputs.
		$welcome_email_input = ! empty( $selected_options['welcome_email'] ) ? $selected_options['welcome_email'] : false;
		
		// Convert various inputs to boolean.
		if ( 'true' === $welcome_email_input || true === $welcome_email_input || 1 === $welcome_email_input || '1' === $welcome_email_input ) {
			$affiliate['welcome_email'] = true;
		} else {
			$affiliate['welcome_email'] = false;
		}

		



		// Insert affiliate into the database.
		$affiliate_id = slicewp_insert_affiliate( $affiliate );
		if ( ! $affiliate_id ) {
			return [
				'status'  => 'error',
				'message' => 'Not able to create new affiliate, try later.',
			];
		} else {
			// Get affiliate name using SliceWP's function (same as in SliceWP admin).
			$affiliate_name = '';
			if ( function_exists( 'slicewp_get_affiliate_name' ) ) {
				$affiliate_name = slicewp_get_affiliate_name( $affiliate_id );
			}
			
			// Prepare comprehensive response data.
			$response = [
				'status'           => 'success',
				'affiliate_id'     => $affiliate_id,
				'user_id'          => $userid,
				'affiliate_name'   => $affiliate_name,
				'user_email'       => $wp_user->user_email,
				'user_login'       => $wp_user->user_login,
				'first_name'       => $wp_user->first_name,
				'last_name'        => $wp_user->last_name,
				'display_name'     => $wp_user->display_name,
				'payment_email'    => $affiliate['payment_email'],
				'website'          => $affiliate['website'],
				'affiliate_status' => $affiliate['status'],
				'date_created'     => $affiliate['date_created'],
				'date_modified'    => $affiliate['date_modified'],
			];
			
			return $response;
		}
	}
}

SliceWPCreateAffiliate::get_instance();
