<?php
/**
 * LicenseRenewed.
 * php version 5.6
 *
 * @category LicenseRenewed
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\EDD\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Integrations\EDD\EDD;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'LicenseRenewed' ) ) :

	/**
	 * LicenseRenewed
	 *
	 * @category LicenseRenewed
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class LicenseRenewed {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'EDD';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'edd_license_renewed';

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
				'label'         => __( 'License Renewed', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'edd_sl_post_license_renewal',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
			];

			return $triggers;

		}

		/**
		 * Trigger listener
		 *
		 * @param int    $license_id License ID.
		 * @param string $new_expiration New expiration date.
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function trigger_listener( $license_id, $new_expiration ) {
			if ( ! function_exists( 'edd_software_licensing' ) ) {
				return;
			}

			$context = EDD::edd_get_license_data( $license_id );
			
			if ( empty( $context ) ) {
				return;
			}
			$context['download_id']         = $context['download_id'];
			$context['renewed_at']          = current_time( 'mysql' );
			$context['new_expiration']      = $new_expiration;
			$context['previous_expiration'] = $context['expiration']; 
			$context['expiration']          = $new_expiration; 

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
	LicenseRenewed::get_instance();

endif;
