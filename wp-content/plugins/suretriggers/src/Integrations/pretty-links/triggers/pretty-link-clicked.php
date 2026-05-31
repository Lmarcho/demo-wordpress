<?php
/**
 * PrettyLinkClicked.
 * php version 5.6
 *
 * @category PrettyLinkClicked
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.1.9
 */

namespace SureTriggers\Integrations\PrettyLinks\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;
use SureTriggers\Integrations\WordPress\WordPress;
use SureTriggers\Integrations\PrettyLinks\PrettyLinks;

if ( ! class_exists( 'PrettyLinkClicked' ) ) :

	/**
	 * PrettyLinkClicked
	 *
	 * @category PrettyLinkClicked
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class PrettyLinkClicked {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'PrettyLinks';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'prettylinks_link_clicked';

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
				'label'         => __( 'A Pretty Link is Clicked', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'prli_record_click',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param array $click_data Click data array containing link_id, click_id, and url.
		 * @return void
		 */
		public function trigger_listener( $click_data ) {

			if ( ! is_array( $click_data ) || ! isset( $click_data['link_id'] ) ) {
				return;
			}

			$link_id  = $click_data['link_id'];
			$click_id = isset( $click_data['click_id'] ) ? $click_data['click_id'] : 0;
			$url      = isset( $click_data['url'] ) ? $click_data['url'] : '';

			global $wpdb;
			$link = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM `{$wpdb->prefix}prli_links` WHERE id = %d",
					$link_id
				),
				ARRAY_A
			);

			if ( ! $link ) {
				return;
			}

			// Check if user is logged in to include user context.
			$user_id = 0;
			$context = [];
			
			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
				$context = WordPress::get_user_context( $user_id );
			} else {
				// For non-logged-in users, provide basic context.
				$context = [
					'user_id'        => 0,
					'user_login'     => '',
					'display_name'   => 'Guest',
					'user_firstname' => '',
					'user_lastname'  => '',
					'user_email'     => '',
					'user_role'      => [],
				];
			}
			
			// Add pretty link data to context.
			$context['link'] = [
				'id'            => $link['id'],
				'name'          => $link['name'],
				'url'           => $link['url'],
				'slug'          => $link['slug'],
				'pretty_url'    => home_url( '/' . $link['slug'] ),
				'description'   => $link['description'],
				'redirect_type' => $link['redirect_type'],
				'clicks'        => $link['clicks'],
				'created_at'    => $link['created_at'],
			];

			// Add click data.
			$context['click'] = [
				'click_id'     => $click_id,
				'url'          => $url,
				'timestamp'    => current_time( 'mysql' ),
				'is_logged_in' => is_user_logged_in(),
			];
			
			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'user_id' => $user_id,
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
	PrettyLinkClicked::get_instance();

endif;
