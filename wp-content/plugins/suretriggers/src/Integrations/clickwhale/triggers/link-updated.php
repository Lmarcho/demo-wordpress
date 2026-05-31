<?php
/**
 * LinkUpdated.
 * php version 5.6
 *
 * @category LinkUpdated
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\ClickWhale\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;
use SureTriggers\Integrations\WordPress\WordPress; 

if ( ! class_exists( 'LinkUpdated' ) ) :

	/**
	 * LinkUpdated
	 *
	 * @category LinkUpdated
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class LinkUpdated {

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
		public $trigger = 'cw_link_updated';

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
				'label'         => __( 'Link Updated', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'clickwhale_link_updated',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param int   $id Link ID.
		 * @param array $post_data POST data.
		 * @return void
		 */
		public function trigger_listener( $id, $post_data ) {
			if ( empty( $id ) || empty( $post_data ) ) {
				return;
			}
		   
			unset( $post_data['action'], $post_data['nonce'], $post_data['submit'] );
			
			global $wpdb;
			$link = $wpdb->get_row( $wpdb->prepare( "SELECT author FROM {$wpdb->prefix}clickwhale_links WHERE id = %d", intval( $id ) ) );
			if ( $link && $link->author ) {
				$post_data['author_id'] = $link->author;

				
					$post_data['author'] = WordPress::get_user_context( $link->author );
				
			}

			$context = [
				'link'    => $post_data,
				'link_id' => $id,
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
	LinkUpdated::get_instance();

endif;
