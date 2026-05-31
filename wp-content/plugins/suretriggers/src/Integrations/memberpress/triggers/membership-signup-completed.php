<?php
/**
 * MembershipCreated.
 * php version 5.6
 *
 * @category MembershipCreated
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\MemberPress\Triggers;

use MeprTransaction;
use SureTriggers\Controllers\AutomationController;
use SureTriggers\Integrations\MemberPress\MemberPress;
use SureTriggers\Integrations\WordPress\WordPress;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'MembershipCreated' ) ) :

	/**
	 * MembershipCreated
	 *
	 * @category MembershipCreated
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 */
	class MembershipCreated {


		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'MemberPress';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'mepr-event-member-signup-completed';

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
				'label'         => __( 'Membership Created', 'suretriggers' ),
				'action'        => $this->trigger,
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;

		}


		/**
		 * Trigger listener
		 * This will trigger only for initial member signup completion, not recurring payments.
		 *
		 * @param object $event Event data.
		 *
		 * @return void
		 */
		public function trigger_listener( $event ) {
			if ( ! class_exists( 'MeprEvent' ) || ! $event instanceof \MeprEvent ) {
				return;
			}

			$user_id = $event->evt_id;
			if ( empty( $user_id ) ) {
				return;
			}

			// Get the user's transactions to find the related membership.
			$user = get_user_by( 'ID', $user_id );
			if ( ! $user ) {
				return;
			}

			// Get the most recent completed transaction for this user.
			if ( ! class_exists( 'MeprTransaction' ) ) {
				return;
			}
			
			$transactions          = \MeprTransaction::get_all_by_user_id( $user_id, false, true );
			$completed_transaction = null;
			foreach ( $transactions as $txn ) {
				if ( \MeprTransaction::$complete_str === $txn->status ) {
					$completed_transaction = $txn;
					break;
				}
			}

			if ( ! $completed_transaction ) {
				return;
			}

			// Get membership/product details.
			$membership = get_post( $completed_transaction->product_id );
			if ( ! $membership ) {
				return;
			}
			$membership_context = [
				'membership_title'              => $membership->post_title,
				'membership_url'                => get_permalink( $completed_transaction->product_id ),
				'membership_featured_image_id'  => get_post_meta( $completed_transaction->product_id, '_thumbnail_id', true ),
				'membership_featured_image_url' => get_the_post_thumbnail_url( $completed_transaction->product_id ),
				'amount'                        => $completed_transaction->amount,
				'total'                         => $completed_transaction->total,
				'tax_amount'                    => $completed_transaction->tax_amount,
				'tax_rate'                      => $completed_transaction->tax_rate,
				'trans_num'                     => $completed_transaction->trans_num,
				'status'                        => $completed_transaction->status,
				'subscription_id'               => $completed_transaction->subscription_id,
				'transaction_id'                => $completed_transaction->id,
			];
			
			$membership_id = $completed_transaction->product_id;
		   
			$context                  = array_merge(
				WordPress::get_user_context( $user_id ),
				$membership_context,
				[
					'signup_date' => $event->created_at,
				]
			);
			$context['membership_id'] = $membership_id;
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
	MembershipCreated::get_instance();

endif;
