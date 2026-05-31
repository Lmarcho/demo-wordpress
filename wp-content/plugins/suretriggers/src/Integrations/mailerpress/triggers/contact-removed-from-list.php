<?php
/**
 * ContactRemovedFromList.
 * php version 5.6
 *
 * @category ContactRemovedFromList
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\MailerPress\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;
use SureTriggers\Integrations\WordPress\WordPress;

if ( ! class_exists( 'ContactRemovedFromList' ) ) :

	/**
	 * ContactRemovedFromList
	 *
	 * @category ContactRemovedFromList
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class ContactRemovedFromList {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'MailerPress';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'mailerpress_contact_removed_from_list';

		use SingletonLoader;

		/**
		 * Constructor
		 *
		 * @since  1.0.0
		 */
		public function __construct() {
			add_filter( 'sure_trigger_register_trigger', [ $this, 'register' ] );
		}

		/**
		 * Register action.
		 *
		 * @param array $triggers trigger data.
		 * @return array
		 */
		public function register( $triggers ) {
			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'Contact Removed from List', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'mailerpress_contact_list_removed',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param int $contact_id Contact ID.
		 * @param int $list_id List ID.
		 * @return void
		 */
		public function trigger_listener( $contact_id, $list_id ) {
			if ( empty( $contact_id ) || empty( $list_id ) ) {
				return;
			}

			global $wpdb;

			// Get contact details.
			$contact = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM `' . esc_sql( $wpdb->prefix . 'mailerpress_contact' ) . '` WHERE contact_id = %d',
					$contact_id
				)
			);

			if ( ! $contact ) {
				return;
			}

			// Get list details.
			$list = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM `' . esc_sql( $wpdb->prefix . 'mailerpress_lists' ) . '` WHERE list_id = %d',
					$list_id
				)
			);

			if ( ! $list ) {
				return;
			}

			$context['contact'] = [
				'id'                  => (int) $contact->contact_id,
				'email'               => $contact->email,
				'first_name'          => $contact->first_name,
				'last_name'           => $contact->last_name,
				'subscription_status' => $contact->subscription_status,
				'created_at'          => $contact->created_at,
				'updated_at'          => $contact->updated_at,
			];
			$context['list']    = [
				'id'          => (int) $list->list_id,
				'name'        => $list->name,
				'description' => $list->description,
				'created_at'  => $list->created_at,
				'updated_at'  => $list->updated_at,
			];

			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $context,
				]
			);
		}
	}

	/**
	 * Ignore false positive
	 *
	 * @psalm-suppress UndefinedMethod
	 */
	ContactRemovedFromList::get_instance();

endif;
