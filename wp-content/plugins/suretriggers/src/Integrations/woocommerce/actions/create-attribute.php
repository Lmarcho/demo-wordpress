<?php
/**
 * CreateAttribute.
 * php version 5.6
 *
 * @category CreateAttribute
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Woocommerce\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Integrations\WooCommerce\WooCommerce;
use SureTriggers\Traits\SingletonLoader;

/**
 * CreateAttribute
 *
 * @category CreateAttribute
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateAttribute extends AutomateAction {

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
	public $action = 'wc_create_attribute';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Attribute', 'suretriggers' ),
			'action'   => 'wc_create_attribute',
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
	 * @return void|array|bool
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'WC' ) || ! function_exists( 'wc_create_attribute' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WooCommerce not available', 'suretriggers' ),
			];
		}

		// Validate required fields.
		foreach ( $fields as $field ) {
			if ( array_key_exists( 'validationProps', $field ) && empty( $selected_options[ $field['name'] ] ) ) {
				return [
					'status'  => 'error',
					'message' => __( 'Required field is missing: ', 'suretriggers' ) . $field['name'],
				];
			}
		}

		// Get attribute parameters.
		$attribute_name    = ! empty( $selected_options['attribute_name'] ) ? sanitize_text_field( $selected_options['attribute_name'] ) : '';
		$attribute_slug    = ! empty( $selected_options['attribute_slug'] ) ? sanitize_text_field( $selected_options['attribute_slug'] ) : '';
		$attribute_orderby = ! empty( $selected_options['order_by'] ) ? sanitize_text_field( $selected_options['order_by'] ) : 'menu_order';
		$has_archives      = ! empty( $selected_options['has_archives'] ) ? (bool) $selected_options['has_archives'] : false;

		if ( empty( $attribute_name ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Attribute name is required', 'suretriggers' ),
			];
		}

		// Generate slug if not provided.
		if ( empty( $attribute_slug ) ) {
			$attribute_slug = sanitize_title( $attribute_name );
		}

		// Sanitize attribute slug.
		$attribute_slug = wc_sanitize_taxonomy_name( wp_unslash( $attribute_slug ) );


		// Validate orderby.
		$valid_orderby = [ 'menu_order', 'name', 'name_num', 'id' ];
		if ( ! in_array( $attribute_orderby, $valid_orderby, true ) ) {
			$attribute_orderby = 'menu_order';
		}

		try {
			// Check if attribute already exists.
			$existing_attributes = wc_get_attribute_taxonomies();
			foreach ( $existing_attributes as $existing_attribute ) {
				if ( $existing_attribute->attribute_name === $attribute_slug ) {
					return [
						'status'  => 'error',
						'message' => __( 'Attribute with this slug already exists', 'suretriggers' ),
					];
				}
			}

			// Prepare attribute data.
			$attributes_args = [
				'name'         => $attribute_name,
				'slug'         => $attribute_slug,
				'order_by'     => $attribute_orderby,
				'has_archives' => $has_archives ? 1 : 0,
			];

			// Create the attribute.
			$attribute_id = wc_create_attribute( $attributes_args );

			if ( is_wp_error( $attribute_id ) ) {
				return [
					'status'  => 'error',
					'message' => __( 'Failed to create attribute: ', 'suretriggers' ) . $attribute_id->get_error_message(),
				];
			}

			// Get the created attribute details.
			$created_attribute = wc_get_attribute( $attribute_id );
			$taxonomy_name     = wc_attribute_taxonomy_name( $attribute_slug );

			// Register the taxonomy if it doesn't exist.
			if ( ! taxonomy_exists( $taxonomy_name ) ) {
				register_taxonomy(
					$taxonomy_name,
					'product',
					[
						'labels'       => [
							'name' => $attribute_name,
						],
						'hierarchical' => false,
						'public'       => $has_archives,
						'show_ui'      => false,
						'query_var'    => true,
						'rewrite'      => false,
					]
				);
			}

			if ( $created_attribute ) {
				return [
					'status'            => 'success',
					'message'           => __( 'Attribute created successfully', 'suretriggers' ),
					'attribute_id'      => $attribute_id,
					'attribute_name'    => $created_attribute->name,
					'attribute_slug'    => $created_attribute->slug,
					'attribute_type'    => $created_attribute->type,
					'attribute_orderby' => $created_attribute->order_by,
					'has_archives'      => (bool) $created_attribute->has_archives,
					'taxonomy_name'     => $taxonomy_name,
					'attribute_details' => (array) $created_attribute,
				];
			} else {
				return [
					'status'  => 'error',
					'message' => __( 'Attribute created but could not retrieve details', 'suretriggers' ),
				];
			}       
		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => __( 'Error creating attribute: ', 'suretriggers' ) . $e->getMessage(),
			];
		}
	}
}

CreateAttribute::get_instance();
