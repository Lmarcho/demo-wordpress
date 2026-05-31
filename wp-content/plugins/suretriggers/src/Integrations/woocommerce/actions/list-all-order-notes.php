<?php
/**
 * ListAllOrderNotes.
 * php version 5.6
 *
 * @category ListAllOrderNotes
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WooCommerce\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Integrations\WooCommerce\WooCommerce;
use SureTriggers\Traits\SingletonLoader;
use WP_Error;

/**
 * ListAllOrderNotes
 *
 * @category ListAllOrderNotes
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class ListAllOrderNotes extends AutomateAction {

	use SingletonLoader;

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
	public $action = 'wc_list_all_order_notes';

	/**
	 * Register the action.
	 *
	 * @param array $actions Actions array.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'List All Order Notes', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];

		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id          User ID.
	 * @param int   $automation_id    Automation ID.
	 * @param array $fields           Fields.
	 * @param array $selected_options Selected options.
	 * @return array|WP_Error
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return [
				'success' => false,
				'message' => __( 'WooCommerce functions not available.', 'suretriggers' ),
			];
		}

		$order_id = isset( $selected_options['order_id'] ) ? intval( $selected_options['order_id'] ) : 0;

		if ( empty( $order_id ) ) {
			return [
				'success' => false,
				'message' => __( 'Order ID is required.', 'suretriggers' ),
			];
		}

		// Get the order.
		$order = wc_get_order( $order_id );

		if ( ! $order || ! is_object( $order ) ) {
			return [
				'success' => false,
				'message' => sprintf( __( 'Order with ID %d not found.', 'suretriggers' ), $order_id ),
			];
		}

		// Verify it's a valid WooCommerce order.
		if ( ! method_exists( $order, 'get_id' ) ) {
			return [
				'success' => false,
				'message' => __( 'Invalid order object retrieved.', 'suretriggers' ),
			];
		}

		// Get order notes with default settings.
		$notes_data = $this->get_order_notes( $order_id );

		if ( is_array( $notes_data ) && isset( $notes_data['error'] ) ) {
			return [
				'success' => false,
				'message' => $notes_data['error'],
			];
		}

		// Get order basic information.
		$order_date_created = $order->get_date_created();
		$order_info         = [
			'order_id'       => $order->get_id(),
			'order_number'   => method_exists( $order, 'get_order_number' ) ? $order->get_order_number() : $order->get_id(),
			'order_status'   => $order->get_status(),
			'order_total'    => $order->get_total(),
			'customer_id'    => method_exists( $order, 'get_customer_id' ) ? $order->get_customer_id() : 0,
			'customer_email' => method_exists( $order, 'get_billing_email' ) ? $order->get_billing_email() : '',
			'order_date'     => $order_date_created ? $order_date_created->date( 'Y-m-d H:i:s' ) : '',
		];

		$order_number  = method_exists( $order, 'get_order_number' ) ? $order->get_order_number() : $order->get_id();
		$response_data = [
			'success'       => true,
			'order_info'    => $order_info,
			'notes'         => isset( $notes_data['notes'] ) ? $notes_data['notes'] : [],
			'notes_count'   => isset( $notes_data['total_count'] ) ? $notes_data['total_count'] : 0,
			'notes_summary' => isset( $notes_data['summary'] ) ? $notes_data['summary'] : [],
			'message'       => sprintf( __( 'Retrieved %1$d order notes for order #%2$s.', 'suretriggers' ), isset( $notes_data['total_count'] ) ? $notes_data['total_count'] : 0, $order_number ),
		];

		return $response_data;
	}

	/**
	 * Get order notes with filtering and formatting.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	private function get_order_notes( $order_id ) {
		// Try WooCommerce native method first.
		$wc_notes = [];
		if ( function_exists( 'wc_get_order_notes' ) ) {
			$wc_notes = wc_get_order_notes(
				[
					'order_id' => $order_id,
					'limit'    => -1,
				] 
			);
		}

		// Get all order notes using WordPress comments.
		$args = [
			'post_id'    => $order_id,
			'status'     => 'approve',
			'type'       => '',
			'meta_query' => [],
		];

		// Apply default ordering.
		$args['orderby'] = 'comment_date';
		$args['order']   = 'DESC';

		// No limit applied in simplified version.

		// Get comments (order notes) - try different approaches.
		$comments = get_comments( $args );
		
		// If no comments found, try with different parameters.
		if ( empty( $comments ) ) {
			// Try without type restriction.
			unset( $args['type'] );
			$comments = get_comments( $args );
		}
		
		// If still no comments, try direct database query.
		if ( empty( $comments ) ) {
			global $wpdb;
			$comments = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->comments} WHERE comment_post_ID = %d AND comment_type IN ('', 'order_note') ORDER BY comment_date DESC",
					$order_id
				) 
			);
		}

		if ( empty( $comments ) ) {
			return [
				'notes'       => [],
				'total_count' => 0,
				'summary'     => [
					'customer_notes' => 0,
					'private_notes'  => 0,
					'system_notes'   => 0,
				],
			];
		}

		$formatted_notes = [];
		$summary         = [
			'customer_notes' => 0,
			'private_notes'  => 0,
			'system_notes'   => 0,
		];

		foreach ( $comments as $comment ) {
			$note_data = $this->format_order_note( $comment );

			// Apply note type filtering.
			$note_visibility = $this->determine_note_type( $comment );
			
			// Count for summary.
			switch ( $note_visibility ) {
				case 'customer':
					$summary['customer_notes']++;
					break;
				case 'private':
					$summary['private_notes']++;
					break;
				case 'system':
					$summary['system_notes']++;
					break;
			}

			// Apply filters would go here if parameters were passed.
			// For simplified version, we include all notes.

			$note_data['note_type'] = $note_visibility;
			$formatted_notes[]      = $note_data;
		}

		return [
			'notes'       => $formatted_notes,
			'total_count' => count( $formatted_notes ),
			'summary'     => $summary,
		];
	}

	/**
	 * Format order note data.
	 *
	 * @param \WP_Comment $comment Comment object representing order note.
	 * @return array
	 */
	private function format_order_note( $comment ) {
		if ( is_object( $comment ) && ! is_a( $comment, 'WP_Comment' ) ) {
			$comment = (object) $comment;
		}
		
		if ( ! is_object( $comment ) ) {
			return [];
		}

		$note_data = [
			'note_id'          => property_exists( $comment, 'comment_ID' ) ? $comment->comment_ID : '',
			'note_content'     => property_exists( $comment, 'comment_content' ) ? $comment->comment_content : '',
			'date_created'     => property_exists( $comment, 'comment_date' ) ? $comment->comment_date : '',
			'date_created_gmt' => property_exists( $comment, 'comment_date_gmt' ) ? $comment->comment_date_gmt : '',
			'author'           => property_exists( $comment, 'comment_author' ) ? $comment->comment_author : '',
			'author_email'     => property_exists( $comment, 'comment_author_email' ) ? $comment->comment_author_email : '',
		];

		// Get additional meta data.
		$comment_id = property_exists( $comment, 'comment_ID' ) ? (int) $comment->comment_ID : 0;
		$meta_data  = $comment_id ? get_comment_meta( $comment_id ) : [];
		
		// Check if it's a system note.
		$is_customer_note              = $comment_id ? get_comment_meta( $comment_id, 'is_customer_note', true ) : '';
		$note_data['is_customer_note'] = $is_customer_note;

		// Format date for better readability.
		$comment_date                        = property_exists( $comment, 'comment_date' ) ? $comment->comment_date : '';
		$comment_timestamp                   = $comment_date ? strtotime( $comment_date ) : 0;
		$note_data['date_created_formatted'] = $comment_timestamp ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $comment_timestamp ) : '';

		// Calculate time ago.
		$note_data['time_ago'] = $comment_timestamp ? ( human_time_diff( $comment_timestamp, time() ) . ' ' . __( 'ago', 'suretriggers' ) ) : '';

		// Get note author information.
		$user_id = property_exists( $comment, 'user_id' ) ? (int) $comment->user_id : 0;
		if ( $user_id > 0 ) {
			$user = get_user_by( 'id', $user_id );
			if ( $user ) {
				$note_data['author_display_name'] = $user->display_name;
				$note_data['author_role']         = implode( ', ', $user->roles );
			}
		}

		// Check for automated notes.
		$note_data['is_automated'] = $this->is_automated_note( $comment );

		// Extract any additional metadata.
		$note_data['meta_data'] = [];
		if ( is_array( $meta_data ) ) {
			foreach ( $meta_data as $meta_key => $meta_value ) {
				if ( ! in_array( $meta_key, [ 'is_customer_note' ], true ) && is_array( $meta_value ) && isset( $meta_value[0] ) && is_string( $meta_value[0] ) ) {
					$note_data['meta_data'][ $meta_key ] = maybe_unserialize( $meta_value[0] );
				}
			}
		}

		return $note_data;
	}

	/**
	 * Determine the type of order note.
	 *
	 * @param \WP_Comment $comment Comment object.
	 * @return string
	 */
	private function determine_note_type( $comment ) {
		if ( ! is_object( $comment ) ) {
			return 'private';
		}
		$comment_id       = property_exists( $comment, 'comment_ID' ) ? (int) $comment->comment_ID : 0;
		$is_customer_note = $comment_id ? get_comment_meta( $comment_id, 'is_customer_note', true ) : '';
		
		// Customer notes are visible to customers.
		if ( '1' === $is_customer_note || 1 === $is_customer_note ) {
			return 'customer';
		}

		// Check if it's a system-generated note.
		if ( $this->is_automated_note( $comment ) ) {
			return 'system';
		}

		// Default to private note.
		return 'private';
	}

	/**
	 * Check if note is automated/system generated.
	 *
	 * @param \WP_Comment|object $comment Comment object.
	 * @return bool
	 */
	private function is_automated_note( $comment ) {
		// Common indicators of automated notes.
		$automated_indicators = [
			'WooCommerce',
			'Order status changed',
			'Payment complete',
			'Stock reduced',
			'Stock increased',
			'Coupon applied',
			'Refund issued',
		];

		if ( ! is_object( $comment ) ) {
			return false;
		}
		$content = property_exists( $comment, 'comment_content' ) ? $comment->comment_content : '';
		$author  = property_exists( $comment, 'comment_author' ) ? $comment->comment_author : '';

		// Check author.
		if ( 'WooCommerce' === $author || empty( $author ) ) {
			return true;
		}

		// Check content for automated patterns.
		foreach ( $automated_indicators as $indicator ) {
			if ( false !== strpos( $content, $indicator ) ) {
				return true;
			}
		}

		// Check if user_id is 0 (system).
		$user_id = property_exists( $comment, 'user_id' ) ? (int) $comment->user_id : 0;
		if ( 0 === $user_id ) {
			return true;
		}

		return false;
	}
}

ListAllOrderNotes::get_instance();
