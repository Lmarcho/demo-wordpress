<?php
/**
 * AddListsToContact.
 * php version 5.6
 *
 * @category AddListsToContact
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
use FluentCrm\App\Models\Lists;

/**
 * AddListsToContact
 *
 * @category AddListsToContact
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class AddListsToContact extends AutomateAction {


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
	public $action = 'fluentcrm_add_lists_to_contact';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Add Lists to Contact', 'suretriggers' ),
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
				'message' => __( 'FluentCrmApi function not found.', 'suretriggers' ), 
				
			];
		}
		$contact_api = FluentCrmApi( 'contacts' );

		$contact = $contact_api->getContact( trim( $selected_options['contact_email'] ) );

		if ( is_null( $contact ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid contact.', 'suretriggers' ), 
				
			];
		}

		$list_ids      = [];
		$list_names    = [];
		$selected_list = $selected_options['list_id'];
		if ( ! empty( $selected_list ) ) {
			if ( is_array( $selected_list ) ) {
				foreach ( $selected_list as $list ) {
					$list_ids[]   = $list['value'];
					$list_names[] = esc_html( $list['label'] );
				}
			} elseif ( is_string( $selected_list ) ) {
				$lists_arr = array_filter( explode( ',', $selected_list ) );
				if ( ! class_exists( 'FluentCrm\App\Models\Lists' ) ) {
					return [
						'status'  => 'error',
						'message' => __( 'Lists model not found.', 'suretriggers' ), 
						
					];
				}
				if ( ! empty( $lists_arr ) ) {
					foreach ( $lists_arr as $list ) {
						$exist = Lists::where( 'title', $list )
						->orWhere( 'slug', $list )
						->first();
						if ( is_null( $exist ) ) {
							$new_list     = Lists::create(
								[
									'title' => $list,
								]
							);
							$list_ids[]   = $new_list->id;
							$list_names[] = esc_html( $new_list->title );
						} else {
							$list_ids[]   = $exist->id;
							$list_names[] = esc_html( $exist->title );
						}
					}
				}
			}
		}

		$contact->attachLists( $list_ids );

		$context                   = [];
		$context['list_names']     = implode( ',', $list_names );
		$context['full_name']      = $contact->full_name;
		$context['first_name']     = $contact->first_name;
		$context['last_name']      = $contact->last_name;
		$context['contact_owner']  = $contact->contact_owner;
		$context['company_id']     = $contact->company_id;
		$context['email']          = $contact->email;
		$context['address_line_1'] = $contact->address_line_1;
		$context['address_line_2'] = $contact->address_line_2;
		$context['postal_code']    = $contact->postal_code;
		$context['city']           = $contact->city;
		$context['state']          = $contact->state;
		$context['country']        = $contact->country;
		$context['phone']          = $contact->phone;
		$context['status']         = $contact->status;
		$context['contact_type']   = $contact->contact_type;
		$context['source']         = $contact->source;
		$context['date_of_birth']  = $contact->date_of_birth;
		return $context;
	}

}

AddListsToContact::get_instance();
