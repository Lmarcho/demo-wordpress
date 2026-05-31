<?php
/**
 * ListCreated.
 * php version 5.6
 *
 * @category ListCreated
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

if ( ! class_exists( 'ListCreated' ) ) :

	/**
	 * ListCreated
	 *
	 * @category ListCreated
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class ListCreated {

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
		public $trigger = 'mailerpress_list_created';

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
				'label'         => __( 'List Created', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'mailerpress_list_created',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param int $list_id List ID.
		 * @return void
		 */
		public function trigger_listener( $list_id ) {
			if ( empty( $list_id ) ) {
				return;
			}

			global $wpdb;

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
			$context         = WordPress::get_user_context( get_current_user_id() );
			$context['list'] = [
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
	ListCreated::get_instance();

endif;
