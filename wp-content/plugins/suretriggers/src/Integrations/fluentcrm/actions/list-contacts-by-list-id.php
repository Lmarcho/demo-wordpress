<?php
/**
 * ListContactsByListID.
 * php version 5.6
 *
 * @category ListContactsByListID
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCRM\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * ListContactsByListID
 *
 * @category ListContactsByListID
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class ListContactsByListID extends AutomateAction {


	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'FluentCRM';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'fluentcrm_list_contacts_by_list_id';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'List Contacts by List ID', 'suretriggers' ),
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
	 * @return array
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		
		if ( ! function_exists( 'FluentCrmApi' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCRM is not active.', 'suretriggers' ),
			];
		}

		$list_id = $selected_options['list_id'];

		if ( empty( $list_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'List ID is required.', 'suretriggers' ),
			];
		}

		$contact_api = FluentCrmApi( 'contacts' );
		$contacts    = $contact_api->getInstance()
			->with( [ 'tags', 'lists' ] )
			->filterByLists( [ $list_id ] )
			->get();
		
		$contacts_array = json_decode( $contacts, true );

		if ( empty( $contacts_array ) ) {
			return [
				'message'        => __( 'No contacts found in this list', 'suretriggers' ),
				'status'         => 'success',
				'contacts_count' => 0,
				'contacts'       => [],
			];
		}

		$context = [];
		if ( is_array( $contacts_array ) ) {
			foreach ( $contacts_array as $key => $contact ) {
				$context['contacts'][ $key ]['id']             = $contact['id'];
				$context['contacts'][ $key ]['user_id']        = $contact['user_id'];
				$context['contacts'][ $key ]['full_name']      = $contact['full_name'];
				$context['contacts'][ $key ]['first_name']     = $contact['first_name'];
				$context['contacts'][ $key ]['last_name']      = $contact['last_name'];
				$context['contacts'][ $key ]['contact_owner']  = $contact['contact_owner'];
				$context['contacts'][ $key ]['company_id']     = $contact['company_id'];
				$context['contacts'][ $key ]['email']          = $contact['email'];
				$context['contacts'][ $key ]['address_line_1'] = $contact['address_line_1'];
				$context['contacts'][ $key ]['address_line_2'] = $contact['address_line_2'];
				$context['contacts'][ $key ]['postal_code']    = $contact['postal_code'];
				$context['contacts'][ $key ]['city']           = $contact['city'];
				$context['contacts'][ $key ]['state']          = $contact['state'];
				$context['contacts'][ $key ]['country']        = $contact['country'];
				$context['contacts'][ $key ]['phone']          = $contact['phone'];
				$context['contacts'][ $key ]['status']         = $contact['status'];
				$context['contacts'][ $key ]['contact_type']   = $contact['contact_type'];
				$context['contacts'][ $key ]['source']         = $contact['source'];
				$context['contacts'][ $key ]['date_of_birth']  = $contact['date_of_birth'];
				$context['contacts'][ $key ]['created_at']     = $contact['created_at'];
				$context['contacts'][ $key ]['updated_at']     = $contact['updated_at'];
				$context['contacts'][ $key ]['tags']           = $contact['tags'];
				$context['contacts'][ $key ]['lists']          = $contact['lists'];
				
				$custom_data = $contact['custom_fields'];
				if ( ! empty( $custom_data ) ) {
					foreach ( $custom_data as $custom_key => $field ) {
						if ( is_array( $field ) ) {
							$context['contacts'][ $key ][ $custom_key ] = implode( ',', $field );
						} else {
							$context['contacts'][ $key ][ $custom_key ] = $field;
						}
					}
				}
			}
		}
		
		$context['status']         = 'success';
		$context['contacts_count'] = is_array( $contacts_array ) ? count( $contacts_array ) : 0;
		$context['list_id']        = $list_id;

		return $context;
	}

}

ListContactsByListID::get_instance();
