<?php
/**
 * DeleteTag.
 * php version 5.6
 *
 * @category DeleteTag
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCRM\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use FluentCrm\App\Models\Tag;

/**
 * DeleteTag
 *
 * @category DeleteTag
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class DeleteTag extends AutomateAction {


	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'FluentCRM';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'fluentcrm_delete_tag';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Delete Tag', 'suretriggers' ),
			'action'   => $this->action,
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
	 * @return array
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		
		if ( ! class_exists( 'FluentCrm\App\Models\Tag' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCRM is not active.', 'suretriggers' ),
			];
		}

		$tag_id = intval( $selected_options['tag_id'] );
		$tag    = Tag::find( $tag_id );

		if ( ! $tag ) {
			return [
				'status'  => 'error',
				'message' => __( 'Tag not found.', 'suretriggers' ),
			];
		}

		$context = [
			'tag_id'    => $tag->id,
			'tag_title' => $tag->title,
			'tag_slug'  => $tag->slug,
		];

		$tag->delete();

		return [
			'success'     => true,
			'message'     => __( 'Tag deleted successfully.', 'suretriggers' ),
			'deleted_tag' => $context,
		];
	}

}

DeleteTag::get_instance();
