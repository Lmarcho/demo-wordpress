<?php
/**
 * LinkClicked.
 * php version 5.6
 *
 * @category LinkClicked
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

if ( ! class_exists( 'LinkClicked' ) ) :

	/**
	 * LinkClicked
	 *
	 * @category LinkClicked
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class LinkClicked {

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
		public $trigger = 'cw_link_clicked';

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
				'label'         => __( 'Link Clicked', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'clickwhale/link_clicked',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 3,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param array $link Link data.
		 * @param int   $link_id Link ID.
		 * @param int   $user_id User ID.
		 * @return void
		 */
		public function trigger_listener( $link, $link_id, $user_id ) {
			
			if ( empty( $link ) || empty( $link_id ) ) {
				return;
			}
			
			global $wpdb;
			$link_author = $wpdb->get_row( $wpdb->prepare( "SELECT author FROM {$wpdb->prefix}clickwhale_links WHERE id = %d", intval( $link_id ) ) );

			if ( $link_author && $link_author->author ) {
				$link['author_id'] = $link_author->author;

				
					$link['author'] = WordPress::get_user_context( $link_author->author );
				
			}
		   
			$context = [
				'link' => $link,
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
	LinkClicked::get_instance();

endif;
