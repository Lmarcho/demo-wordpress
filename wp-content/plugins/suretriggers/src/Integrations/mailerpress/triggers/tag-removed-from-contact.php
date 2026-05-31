<?php
/**
 * TagRemovedFromContact.
 * php version 5.6
 *
 * @category TagRemovedFromContact
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

if ( ! class_exists( 'TagRemovedFromContact' ) ) :

	/**
	 * TagRemovedFromContact
	 *
	 * @category TagRemovedFromContact
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class TagRemovedFromContact {

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
		public $trigger = 'mailerpress_tag_removed_from_contact';

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
				'label'         => __( 'Tag Removed from Contact', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'mailerpress_contact_tag_removed',
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
		 * @param int $tag_id Tag ID.
		 * @return void
		 */
		public function trigger_listener( $contact_id, $tag_id ) {
			if ( empty( $contact_id ) || empty( $tag_id ) ) {
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

			// Get tag details.
			$tag = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM `' . esc_sql( $wpdb->prefix . 'mailerpress_tags' ) . '` WHERE tag_id = %d',
					$tag_id
				)
			);

			if ( ! $tag ) {
				return;
			}

			$context            = WordPress::get_user_context( $contact->contact_id );
			$context['contact'] = [
				'id'                  => (int) $contact->contact_id,
				'email'               => $contact->email,
				'first_name'          => $contact->first_name,
				'last_name'           => $contact->last_name,
				'subscription_status' => $contact->subscription_status,
				'created_at'          => $contact->created_at,
				'updated_at'          => $contact->updated_at,
			];
			$context['tag']     = [
				'id'   => (int) $tag->tag_id,
				'name' => $tag->name,
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
	TagRemovedFromContact::get_instance();

endif;
