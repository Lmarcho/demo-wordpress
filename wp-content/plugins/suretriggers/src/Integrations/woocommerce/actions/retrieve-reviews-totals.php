<?php
/**
 * RetrieveReviewsTotals.
 * php version 5.6
 *
 * @category RetrieveReviewsTotals
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WooCommerce\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * RetrieveReviewsTotals
 *
 * @category RetrieveReviewsTotals
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class RetrieveReviewsTotals extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'WooCommerce';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'wc_retrieve_reviews_totals';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Retrieve Reviews Totals', 'suretriggers' ),
			'action'   => 'wc_retrieve_reviews_totals',
			'function' => [ $this, 'action_listener' ],
		];
		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id user_id.
	 * @param int   $automation_id automation_id.
	 * @param array $fields fields.
	 * @param array $selected_options selectedOptions.
	 *
	 * @return array|null
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WooCommerce is not installed or activated.', 'suretriggers' ), 
				
			];
		}

		$reviews = get_comments(
			[
				'post_type' => 'product',
				'status'    => 'approve',
				'meta_key'  => 'rating',
			]
		);

		if ( ! is_array( $reviews ) ) {
			$reviews = [];
		}

		$rating_counts = [];

		foreach ( $reviews as $review ) {
			if ( ! is_object( $review ) || ! property_exists( $review, 'comment_ID' ) ) {
				continue;
			}
			$rating = get_comment_meta( (int) $review->comment_ID, 'rating', true );
			if ( $rating ) {
				if ( ! isset( $rating_counts[ $rating ] ) ) {
					$rating_counts[ $rating ] = 0;
				}
				$rating_counts[ $rating ]++;
			}
		}

		$rating_types = [
			'rated_1_out_of_5' => 'Rated 1 out of 5',
			'rated_2_out_of_5' => 'Rated 2 out of 5',
			'rated_3_out_of_5' => 'Rated 3 out of 5',
			'rated_4_out_of_5' => 'Rated 4 out of 5',
			'rated_5_out_of_5' => 'Rated 5 out of 5',
		];

		$result = [];
		foreach ( $rating_types as $slug => $name ) {
			$rating_number = substr( $slug, 6, 1 );
			$result[]      = [
				'slug'  => $slug,
				'name'  => $name,
				'total' => isset( $rating_counts[ $rating_number ] ) ? $rating_counts[ $rating_number ] : 0,
			];
		}

		return $result;
	}
}

RetrieveReviewsTotals::get_instance();
