<?php
/**
 * RetrieveProductsTotals.
 * php version 5.6
 *
 * @category RetrieveProductsTotals
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
 * RetrieveProductsTotals
 *
 * @category RetrieveProductsTotals
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class RetrieveProductsTotals extends AutomateAction {

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
	public $action = 'wc_retrieve_products_totals';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Retrieve Products Totals', 'suretriggers' ),
			'action'   => 'wc_retrieve_products_totals',
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

		$product_types = [
			'external' => 'External/Affiliate product',
			'grouped'  => 'Grouped product',
			'simple'   => 'Simple product',
			'variable' => 'Variable product',
		];

		// Initialize counts for all product types.
		$product_counts = array_fill_keys( array_keys( $product_types ), 0 );

		// Get product IDs by type using WooCommerce's built-in functions.
		foreach ( array_keys( $product_types ) as $type ) {
			$args = [
				'type'   => $type,
				'status' => 'publish',
				'limit'  => -1,
				'return' => 'ids',
			];
			
			$products                = wc_get_products( $args );
			$product_counts[ $type ] = is_array( $products ) ? count( $products ) : 0;
		}

		$result = [];
		foreach ( $product_types as $slug => $name ) {
			$result[] = [
				'slug'  => $slug,
				'name'  => $name,
				'total' => $product_counts[ $slug ],
			];
		}

		return $result;
	}
}

RetrieveProductsTotals::get_instance();
