<?php
/**
 * LinkDeleted.
 * php version 5.6
 *
 * @category LinkDeleted
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\ClickWhale\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'LinkDeleted' ) ) :

	/**
	 * LinkDeleted
	 *
	 * @category LinkDeleted
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class LinkDeleted {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'ClickWhale';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'cw_link_deleted';

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
				'label'         => __( 'Link Deleted', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'clickwhale_link_deleted',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param array $ids Array of deleted link IDs.
		 * @return void
		 */
		public function trigger_listener( $ids ) {
			if ( empty( $ids ) || ! is_array( $ids ) ) {
				return;
			}

			foreach ( $ids as $link_id ) {
				$context = [
					'link_id'    => $link_id,
					'deleted_at' => current_time( 'mysql' ),
				];

				AutomationController::sure_trigger_handle_trigger(
					[
						'trigger' => $this->trigger,
						'context' => $context,
					]
				);
			}
		}
	}

	/**
	 * Ignore false positive
	 *
	 * @psalm-suppress UndefinedMethod
	 */
	LinkDeleted::get_instance();

endif;
