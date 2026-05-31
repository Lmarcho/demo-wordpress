<?php
/**
 * BuddyBoss integration class file
 *
 * @package  SureTriggers
 * @since 1.0.0
 */

namespace SureTriggers\Integrations\BuddyBoss;

use SureTriggers\Controllers\IntegrationsController;
use SureTriggers\Integrations\Integrations;
use SureTriggers\Traits\SingletonLoader;

/**
 * Class BuddyBoss
 *
 * @package SureTriggers\Integrations\BuddyBoss
 */
class BuddyBoss extends Integrations {

	use SingletonLoader;

	/**
	 * ID of the integration
	 *
	 * @var string
	 */
	protected $id = 'BuddyBoss';

	/**
	 * BuddyBoss constructor.
	 */
	public function __construct() {
		$this->name = __( 'BuddyBoss', 'suretriggers' );
		parent::__construct();
	}

	/**
	 * Check if content has links.
	 *
	 * @param string $content content.
	 * @return array|string
	 */
	public static function st_content_has_links( $content ) {
		// Define a regular expression pattern to match URLs.
		$pattern = '/<a\b[^>]*href=["\']([^"\'#]+)/i';

		// Use preg_match_all to find all links in the content.
		preg_match_all( $pattern, $content, $matches );
	 
		// Return the array of matched links.
		return $matches[1];
	}

	/**
	 * Check plugin is installed.
	 *
	 * @return bool
	 */
	public function is_plugin_installed() {
		if ( function_exists( 'buddypress' ) && isset( buddypress()->buddyboss ) && buddypress()->buddyboss ) {
			return true;
		} else {
			return false;
		}
	}
}

IntegrationsController::register( BuddyBoss::class );
