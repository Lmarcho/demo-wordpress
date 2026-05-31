<?php
/**
 * ReplyToPost.
 * php version 5.6
 *
 * @category ReplyToPost
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCommunity\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use FluentCommunity\App\Models\Feed;
use FluentCommunity\App\Models\Comment;
use FluentCommunity\App\Models\Media;
use FluentCommunity\App\Services\Helper;
use FluentCommunity\Framework\Support\Arr;

/**
 * ReplyToPost
 *
 * @category ReplyToPost
 * @package  SureTriggers
 */
class ReplyToPost extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'FluentCommunity';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'fc_reply_to_post';

	use SingletonLoader;

	/**
	 * Register an action.
	 *
	 * @param array $actions Actions array.
	 *
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Reply to Post', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];

		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id        User ID.
	 * @param int   $automation_id  Automation ID.
	 * @param array $fields         Fields.
	 * @param array $selected_options Selected options.
	 *
	 * @return array|void
	 *
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		$post_id       = isset( $selected_options['post_id'] ) ? (int) sanitize_text_field( $selected_options['post_id'] ) : 0;
		$user_email    = isset( $selected_options['user_email'] ) ? sanitize_email( $selected_options['user_email'] ) : '';
		$reply_content = isset( $selected_options['reply_content'] ) ? sanitize_textarea_field( $selected_options['reply_content'] ) : '';
		$media_images  = isset( $selected_options['media_images'] ) ? $this->parse_media_input( $selected_options['media_images'] ) : [];
		// Validate user ID.
		$user = get_user_by( 'email', $user_email );
   
		if ( ! $user ) {
			return [
				'status'  => 'error',
				'message' => 'User not found with the provided email.',
			];
		}

		// Validate post ID and get the post.
		if ( ! $post_id ) {
			return [
				'status'  => 'error',
				'message' => 'Post ID is required.',
			];
		}

		if ( empty( $reply_content ) ) {
			return [
				'status'  => 'error',
				'message' => 'Reply content is required.',
			];
		}

		// Check if FluentCommunity classes exist.
		if ( ! class_exists( '\FluentCommunity\App\Models\Feed' ) || ! class_exists( '\FluentCommunity\App\Models\Comment' ) ) {
			return [
				'status'  => 'error',
				'message' => 'FluentCommunity is not available.',
			];
		}

		// Get the post.
		$post = Feed::find( $post_id );

		if ( ! $post ) {
			return [
				'status'  => 'error',
				'message' => 'The specified post does not exist.',
			];
		}

		// Check if user has permission to comment on this post.
		if ( $post->space_id ) {
			// Check if user has access to the space.
			$space_ids = get_user_meta( $user->ID, '_fcom_space_ids', true );
			if ( ! $space_ids || ! is_array( $space_ids ) || ! in_array( $post->space_id, $space_ids ) ) {
				$space = $post->space;
				if ( ! $space || 'public' !== $space->privacy ) {
					return [
						'status'  => 'error',
						'message' => 'User does not have permission to reply to this post.',
					];
				}
			}
		}

		// Process media attachments.
		$media_items = $this->process_media_attachments( $media_images, $user->ID );
		
		// Create the comment/reply.
		$comment_data = [
			'user_id'          => $user->ID,
			'post_id'          => $post_id,
			'parent_id'        => null,
			'message'          => $reply_content,
			'message_rendered' => wp_kses_post( $reply_content ),
			'type'             => 'comment',
			'status'           => 'published',
			'meta'             => [],
		];
		
		// Add media items to meta if available.
		if ( ! empty( $media_items ) ) {
			$comment_data['meta']['media_items'] = $media_items;
		}

		try {
			$comment = Comment::create( $comment_data );

			if ( ! $comment ) {
				return [
					'status'  => 'error',
					'message' => 'Failed to create reply.',
				];
			}

			// Associate media with comment if available.
			if ( ! empty( $media_items ) ) {
				$this->associate_media_with_comment( $media_items, $comment->id, $post_id );
			}

			// Update post comment count.
			$post->increment( 'comments_count' );

			// Fire action hook for other integrations.
			do_action( 'fluent_community_comment_created', $comment, $post );

			return [
				'status'        => 'success',
				'response'      => 'Reply created successfully',
				'comment_id'    => $comment->id,
				'post_id'       => $post_id,
				'user_id'       => $user->ID,
				'reply_content' => $reply_content,
				'comment_url'   => $post->getPermalink() . '?comment_id=' . $comment->id,
				'media_count'   => count( $media_items ),
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => 'Failed to create reply: ' . $e->getMessage(),
			];
		}
	}

	/**
	 * Parse media input from comma separated string or array.
	 *
	 * @param string|array $input Comma separated URLs or array.
	 *
	 * @return array Array of media URLs.
	 */
	private function parse_media_input( $input ) {
		// If already an array, return as is.
		if ( is_array( $input ) ) {
			return $input;
		}
		
		// If string, split by comma and clean up.
		if ( is_string( $input ) && ! empty( $input ) ) {
			$urls         = explode( ',', $input );
			$cleaned_urls = [];
			
			foreach ( $urls as $url ) {
				$url = trim( $url );
				if ( ! empty( $url ) ) {
					// Basic URL validation.
					if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
						$cleaned_urls[] = $url;
					}
				}
			}
			
			return $cleaned_urls;
		}
		
		return [];
	}

	/**
	 * Process media attachments from URLs or file uploads.
	 *
	 * @param array $media_images Array of media URLs or file paths.
	 * @param int   $user_id      User ID.
	 *
	 * @return array Array of processed media items.
	 */
	private function process_media_attachments( $media_images, $user_id ) {
		$media_items = [];
		
		if ( empty( $media_images ) || ! is_array( $media_images ) ) {
			return $media_items;
		}
		
		// Check if FluentCommunity classes are available.
		if ( ! class_exists( '\FluentCommunity\App\Models\Media' ) || ! class_exists( '\FluentCommunity\Framework\Support\Arr' ) ) {
			return $media_items;
		}
		
		foreach ( $media_images as $media_image ) {
			if ( empty( $media_image ) ) {
				continue;
			}
			
			// If it's a URL string, convert to array format.
			if ( is_string( $media_image ) ) {
				$media_image = [ 'url' => $media_image ];
			}
			
			try {
				// Download and create media from URL.
				$media = $this->create_media_from_url( $media_image, $user_id );
				
				if ( $media && isset( $media->id ) ) {
					$settings      = isset( $media->settings ) ? $media->settings : [];
					$media_items[] = [
						'media_id' => $media->id,
						'url'      => isset( $media->public_url ) ? $media->public_url : '',
						'type'     => isset( $media->media_type ) ? $media->media_type : 'image',
						'width'    => Arr::get( $settings, 'width' ),
						'height'   => Arr::get( $settings, 'height' ),
						'provider' => Arr::get( $settings, 'provider', 'uploader' ),
					];
				}
			} catch ( Exception $e ) {
				// Continue processing other media on error.
				unset( $e );
			}
		}
		
		return $media_items;
	}

	/**
	 * Associate processed media with the comment.
	 *
	 * @param array $media_items Array of processed media items.
	 * @param int   $comment_id  Comment ID.
	 * @param int   $feed_id     Feed/Post ID.
	 * @return void
	 */
	private function associate_media_with_comment( $media_items, $comment_id, $feed_id ) {
		// Check if FluentCommunity classes are available.
		if ( ! class_exists( '\FluentCommunity\App\Models\Media' ) || ! class_exists( '\FluentCommunity\Framework\Support\Arr' ) ) {
			return;
		}
		
		foreach ( $media_items as $media_item ) {
			$media_id = Arr::get( $media_item, 'media_id' );
			
			if ( $media_id ) {
				try {
					$media = Media::find( $media_id );
					
					if ( $media ) {
						$media->fill(
							[
								'is_active'     => 1,
								'feed_id'       => $feed_id,
								'object_source' => 'comment',
								'sub_object_id' => $comment_id,
							] 
						);
						$media->save();
					}
				} catch ( Exception $e ) {
					// Continue with other media items on error.
					unset( $e );
				}
			}
		}
	}

	/**
	 * Create media from URL by downloading and uploading to FluentCommunity.
	 *
	 * @param string|array $media_data URL or array with URL.
	 * @param int          $user_id    User ID.
	 *
	 * @return object|null Media object if successful, null otherwise.
	 */
	private function create_media_from_url( $media_data, $user_id ) {
		// Check if FluentCommunity classes are available.
		if ( ! class_exists( '\FluentCommunity\App\Models\Media' ) || ! class_exists( '\FluentCommunity\Framework\Support\Arr' ) ) {
			return null;
		}
		
		$url = is_array( $media_data ) ? Arr::get( $media_data, 'url' ) : $media_data;
		
		if ( empty( $url ) ) {
			return null;
		}
		
		// Validate URL.
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return null;
		}
		
		// Check if media already exists for this URL.
		$existing_media = Media::where( 'settings', 'LIKE', '%' . $url . '%' )
			->where( 'is_active', 1 )
			->first();
			
		if ( $existing_media ) {
			return $existing_media;
		}
		
		try {
			// Download the file.
			$response = wp_remote_get(
				$url,
				[
					'headers' => [
						'User-Agent' => 'FluentCommunity/1.0',
					],
				] 
			);
			
			if ( is_wp_error( $response ) ) {
				return null;
			}
			
			$body         = wp_remote_retrieve_body( $response );
			$content_type = wp_remote_retrieve_header( $response, 'content-type' );
			
			if ( empty( $body ) ) {
				return null;
			}
			
			// Get file info.
			$url_path  = wp_parse_url( $url, PHP_URL_PATH );
			$file_name = $url_path ? basename( $url_path ) : 'unknown_file';
			if ( empty( $file_name ) || strpos( $file_name, '.' ) === false ) {
				// Generate filename based on content type.
				$content_type_string = is_array( $content_type ) ? '' : $content_type;
				$ext                 = $this->get_extension_from_content_type( $content_type_string );
				$file_name           = 'media_' . time() . '.' . $ext;
			}
			
			// Prepare file name for WordPress upload.
			$file_name = sanitize_file_name( $file_name );
			
			// Save file.
			$upload = wp_upload_bits( $file_name, null, $body );
			if ( $upload['error'] ) {
				return null;
			}
			$file_path = $upload['file'];
			
			// Get image file info.
			$file_info  = getimagesize( $file_path );
			$media_type = 'image';
			$settings   = [
				'original_name' => $file_name,
				'source_url'    => $url,
				'provider'      => 'external_download',
			];
			
			if ( $file_info ) {
				$settings['width']  = $file_info[0];
				$settings['height'] = $file_info[1];
			} else {
				// Check if it's a document.
				$allowed_types = [ 'pdf', 'doc', 'docx', 'xls', 'xlsx' ];
				$ext           = pathinfo( $file_name, PATHINFO_EXTENSION );
				if ( in_array( strtolower( $ext ), $allowed_types ) ) {
					$media_type = 'document';
				}
			}
			
			// Create media record.
			$media_data = [
				'user_id'       => $user_id,
				'media_key'     => md5( $url . '_' . time() ),
				'media_type'    => $media_type,
				'driver'        => 'local',
				'media_path'    => $file_path,
				'media_url'     => $upload['url'],
				'settings'      => $settings,
				'object_source' => 'temp',
				'is_active'     => 0, // Will be activated when associated with comment.
			];
			
			$media = Media::create( $media_data );
			
			if ( $media ) {
				return $media;
			}       
		} catch ( Exception $e ) {
			// Clean up file if it was created.
			if ( isset( $file_path ) && file_exists( $file_path ) ) {
				wp_delete_file( $file_path );
			}
		}
		
		return null;
	}
	
	/**
	 * Get file extension from content type.
	 *
	 * @param string $content_type Content type.
	 *
	 * @return string File extension.
	 */
	private function get_extension_from_content_type( $content_type ) {
		$types = [
			'image/jpeg'         => 'jpg',
			'image/jpg'          => 'jpg',
			'image/png'          => 'png',
			'image/gif'          => 'gif',
			'image/webp'         => 'webp',
			'application/pdf'    => 'pdf',
			'text/plain'         => 'txt',
			'application/msword' => 'doc',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
		];
		
		return isset( $types[ $content_type ] ) ? $types[ $content_type ] : 'jpg';
	}
}

ReplyToPost::get_instance();
