<?php
/**
 * ContactUpdated.
 * php version 5.6
 *
 * @category ContactUpdated
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

if ( ! class_exists( 'ContactUpdated' ) ) :

	/**
	 * ContactUpdated
	 *
	 * @category ContactUpdated
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class ContactUpdated {

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
		public $trigger = 'contact_updated_mailerpress';

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
				'label'         => __( 'Contact Updated', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'mailerpress_contact_updated',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param int $contact_id Contact ID.
		 * @return void
		 */
		public function trigger_listener( $contact_id ) {
			if ( empty( $contact_id ) ) {
				return;
			}

			global $wpdb;
			$contact = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}mailerpress_contact WHERE contact_id = %d",
					$contact_id
				)
			);

			if ( ! $contact ) {
				return;
			}

			$context = [
				'contact_id'          => isset( $contact->contact_id ) ? $contact->contact_id : $contact_id,
				'email'               => isset( $contact->email ) ? $contact->email : '',
				'first_name'          => isset( $contact->first_name ) ? $contact->first_name : '',
				'last_name'           => isset( $contact->last_name ) ? $contact->last_name : '',
				'subscription_status' => isset( $contact->subscription_status ) ? $contact->subscription_status : '',
				'opt_in_source'       => isset( $contact->opt_in_source ) ? $contact->opt_in_source : '',
				'opt_in_details'      => isset( $contact->opt_in_details ) ? $contact->opt_in_details : '',
				'created_at'          => isset( $contact->created_at ) ? $contact->created_at : '',
				'updated_at'          => isset( $contact->updated_at ) ? $contact->updated_at : '',
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
	ContactUpdated::get_instance();

endif;
