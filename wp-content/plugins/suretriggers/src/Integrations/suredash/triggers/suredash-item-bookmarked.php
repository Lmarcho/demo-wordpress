<?php
/**
 * SureDashItemBookmarked trigger for handling item bookmark events.
 * php version 5.6
 *
 * @category SureDashItemBookmarked
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\SureDash\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Integrations\WordPress\WordPress;
use SureTriggers\Traits\SingletonLoader;

/**
 * SureDashItemBookmarked
 *
 * @category SureDashItemBookmarked
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class SureDashItemBookmarked {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'SureDash';

	/**
	 * Trigger name.
	 *
	 * @var string
	 */
	public $trigger = 'suredash_item_bookmarked';

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
	 * Register a trigger.
	 *
	 * @param array $triggers triggers.
	 * @return array
	 */
	public function register( $triggers ) {
		$triggers[ $this->integration ][ $this->trigger ] = [
			'label'         => __( 'Item Bookmarked', 'suretriggers' ),
			'action'        => 'suredash_item_bookmark',
			'function'      => [ $this, 'trigger_listener' ],
			'priority'      => 10,
			'accepted_args' => 4,
		];
		return $triggers;
	}

	/**
	 * Trigger listener for item bookmark events.
	 *
	 * @param int    $item_id Item ID.
	 * @param string $item_type Type of item (post, course, etc.).
	 * @param string $status Bookmark status (bookmarked/unbookmarked).
	 * @param int    $user_id User ID.
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function trigger_listener( $item_id, $item_type, $status, $user_id ) {
		if ( ! $item_id || ! $user_id ) {
			return;
		}

		$context                    = WordPress::get_user_context( $user_id );
		$context['item_id']         = $item_id;
		$context['item_type']       = $item_type;
		$context['bookmark_status'] = $status;
		$context['is_bookmarked']   = ( 'bookmarked' === $status ) ? true : false;
		$context['is_unbookmarked'] = ( 'unbookmarked' === $status ) ? true : false;
		
		$item = get_post( $item_id );
		if ( $item instanceof \WP_Post ) {
			$context['item_title']     = $item->post_title;
			$context['item_content']   = $item->post_content;
			$context['item_excerpt']   = $item->post_excerpt;
			$context['item_author']    = $item->post_author;
			$context['item_date']      = $item->post_date;
			$context['item_status']    = $item->post_status;
			$context['item_permalink'] = get_permalink( $item_id );
		}

		AutomationController::sure_trigger_handle_trigger(
			[
				'trigger' => $this->trigger,
				'context' => $context,
			]
		);
	}
}

SureDashItemBookmarked::get_instance();
