<?php
/**
 * CustomerCreated.
 * php version 5.6
 *
 * @category CustomerCreated
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCart\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Integrations\WordPress\WordPress;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'CustomerCreated' ) ) :

	/**
	 * CustomerCreated
	 *
	 * @category CustomerCreated
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class CustomerCreated {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'FluentCart';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'fluentcart_customer_created';

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
				'label'         => __( 'Customer Created', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => [ 'fluent_cart/user/after_register' ],
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 *  Trigger listener
		 *
		 * @param array $data Data from FluentCart user registration containing user_id.
		 *
		 * @return void
		 */
		public function trigger_listener( $data ) {
			if ( empty( $data ) ) {
				return;
			}
			
			// Handle both array and direct user_id formats.
			$user_id = is_array( $data ) && isset( $data['user_id'] ) ? intval( $data['user_id'] ) : intval( $data );
			
			$context            = [];
			$context['user_id'] = $user_id;
			
			if ( $user_id > 0 ) {
				$wp_user = get_user_by( 'ID', $user_id );
				if ( $wp_user ) {
					$context['user_login']      = $wp_user->user_login;
					$context['user_email']      = $wp_user->user_email;
					$context['display_name']    = $wp_user->display_name;
					$context['user_registered'] = $wp_user->user_registered;
					$context['user_roles']      = implode( ', ', $wp_user->roles );
					$context['first_name']      = get_user_meta( $user_id, 'first_name', true );
					$context['last_name']       = get_user_meta( $user_id, 'last_name', true );
				}
			}
			
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
	CustomerCreated::get_instance();

endif;
