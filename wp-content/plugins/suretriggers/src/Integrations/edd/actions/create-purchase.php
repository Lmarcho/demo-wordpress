<?php
/**
 * CreatePurchase.
 * php version 5.6
 *
 * @category CreatePurchase
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\EDD\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use Exception;

/**
 * CreatePurchase
 *
 * @category CreatePurchase
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreatePurchase extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'EDD';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'edd_create_purchase';

	use SingletonLoader;

	/**
	 * Register action.
	 *
	 * @param array $actions action data.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Purchase/Order', 'suretriggers' ),
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
	 * @param array $selected_options selected_options.
	 * @return array|bool
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'edd_insert_payment' ) || ! class_exists( 'EDD_Payment' ) ) {
			return [
				'status'  => 'error',
				'message' => 'EDD plugin is not active.',
			];
		}

		if ( empty( $selected_options['customer_email'] ) || empty( $selected_options['download_id'] ) ) {
			return [
				'status'  => 'error',
				'message' => 'Missing required parameters (customer_email, download_id)',
			];
		}

		$customer_email = sanitize_email( $selected_options['customer_email'] );
		if ( ! is_email( $customer_email ) ) {
			return [
				'status'  => 'error',
				'message' => 'Invalid email address provided',
			];
		}

		$download_id = intval( $selected_options['download_id'] );
		$quantity    = 1;
		$price_id    = isset( $selected_options['price_id'] ) ? intval( $selected_options['price_id'] ) : null;
		
		if ( $download_id <= 0 ) {
			return [
				'status'  => 'error',
				'message' => 'Invalid download ID provided',
			];
		}

		$download_post = get_post( $download_id );
		if ( ! $download_post || 'download' !== $download_post->post_type ) {
			return [
				'status'  => 'error',
				'message' => 'Download not found with ID: ' . $download_id,
			];
		}

		if ( ! function_exists( 'edd_get_download_price' ) ) {
			return [
				'status'  => 'error',
				'message' => 'EDD function edd_get_download_price not found. Please check EDD plugin installation.',
			];
		}


		if ( null !== $price_id ) {
			// Get variable prices and find correct price by ID.
			if ( function_exists( 'edd_get_variable_prices' ) && function_exists( 'edd_has_variable_prices' ) && edd_has_variable_prices( $download_id ) ) {
				$variable_prices = edd_get_variable_prices( $download_id );
				if ( isset( $variable_prices[ $price_id ] ) ) {
					$item_price = floatval( $variable_prices[ $price_id ]['amount'] );
				} else {
					$item_price = edd_get_download_price( $download_id );
				}
			} else {
				$item_price = edd_get_download_price( $download_id, $price_id );
			}
		} else {
			$item_price = edd_get_download_price( $download_id );
		}
		$subtotal = $item_price * $quantity;
		
		$discount_code   = isset( $selected_options['discount_code'] ) ? sanitize_text_field( $selected_options['discount_code'] ) : '';
		$discount_amount = 0;
		$total           = $subtotal;

		if ( ! empty( $discount_code ) && function_exists( 'edd_get_discount_by_code' ) ) {
			$discount = edd_get_discount_by_code( $discount_code );
			if ( $discount && 'active' === $discount->status ) {
				$product_reqs      = $discount->get_product_reqs();
				$excluded_products = $discount->get_excluded_products();
				
				$discount_valid = true;
				
				if ( ! empty( $product_reqs ) && ! in_array( $download_id, $product_reqs ) ) {
					$discount_valid = false;
				}
				
				if ( ! empty( $excluded_products ) && in_array( $download_id, $excluded_products ) ) {
					$discount_valid = false;
				}
				
				$min_amount = $discount->get_min_charge_amount();
				if ( $min_amount > 0 && $subtotal < $min_amount ) {
					$discount_valid = false;
				}
				
				if ( $discount_valid ) {
					$discount_type  = $discount->get_amount_type();
					$discount_value = $discount->get_amount();
					
					if ( 'percent' === $discount_type ) {
						$discount_amount = ( $subtotal * $discount_value ) / 100;
					} else {
						$discount_amount = $discount_value;
					}
					
					if ( $discount_amount > $subtotal ) {
						$discount_amount = $subtotal;
					}
					
					$total = $subtotal - $discount_amount;
				}
			}
		}

		$download_options = [
			'quantity' => $quantity,
		];
		
		if ( null !== $price_id ) {
			$download_options['price_id'] = $price_id;
		}

		$downloads = [
			[
				'id'      => $download_id,
				'options' => $download_options,
			],
		];

		$user_info = [
			'id'         => $user_id,
			'email'      => $customer_email,
			'first_name' => isset( $selected_options['first_name'] ) ? sanitize_text_field( $selected_options['first_name'] ) : '',
			'last_name'  => isset( $selected_options['last_name'] ) ? sanitize_text_field( $selected_options['last_name'] ) : '',
			'discount'   => isset( $selected_options['discount_code'] ) ? sanitize_text_field( $selected_options['discount_code'] ) : 'none',
			'address'    => [
				'line1'   => isset( $selected_options['address_line1'] ) ? sanitize_text_field( $selected_options['address_line1'] ) : '',
				'line2'   => isset( $selected_options['address_line2'] ) ? sanitize_text_field( $selected_options['address_line2'] ) : '',
				'city'    => isset( $selected_options['city'] ) ? sanitize_text_field( $selected_options['city'] ) : '',
				'state'   => isset( $selected_options['state'] ) ? sanitize_text_field( $selected_options['state'] ) : '',
				'zip'     => isset( $selected_options['zip'] ) ? sanitize_text_field( $selected_options['zip'] ) : '',
				'country' => isset( $selected_options['country'] ) ? sanitize_text_field( $selected_options['country'] ) : '',
			],
		];

		$payment_data = [
			'price'        => isset( $selected_options['total_amount'] ) ? floatval( $selected_options['total_amount'] ) : $total,
			'subtotal'     => $subtotal,
			'discount'     => $discount_amount,
			'date'         => isset( $selected_options['date'] ) ? sanitize_text_field( $selected_options['date'] ) : gmdate( 'Y-m-d H:i:s' ),
			'user_email'   => $customer_email,
			'purchase_key' => strtolower( md5( uniqid() ) ),
			'currency'     => isset( $selected_options['currency'] ) ? sanitize_text_field( $selected_options['currency'] ) : ( function_exists( 'edd_get_currency' ) ? edd_get_currency() : 'USD' ),
			'downloads'    => $downloads,
			'user_info'    => $user_info,
			'cart_details' => [],
			'status'       => isset( $selected_options['status'] ) ? sanitize_text_field( $selected_options['status'] ) : 'pending',
			'gateway'      => isset( $selected_options['payment_method'] ) ? sanitize_text_field( $selected_options['payment_method'] ) : 'manual',
			'fees'         => [],
		];

		if ( function_exists( 'edd_use_taxes' ) && function_exists( 'edd_get_tax_rate' ) && edd_use_taxes() ) {
			$tax_rate            = edd_get_tax_rate( $user_info['address']['country'], $user_info['address']['state'] );
			$tax                 = ( $payment_data['price'] * $tax_rate );
			$payment_data['tax'] = $tax;
		}

		$item_options = [];
		if ( null !== $price_id ) {
			$item_options['price_id'] = $price_id;
		}

		$payment_data['cart_details'][] = [
			'name'        => get_the_title( $download_id ),
			'id'          => $download_id,
			'item_number' => [
				'id'      => $download_id,
				'options' => $item_options,
			],
			'price'       => $item_price,
			'item_price'  => $item_price,
			'quantity'    => $quantity,
			'discount'    => $discount_amount,
			'subtotal'    => $subtotal,
			'tax'         => 0,
			'fees'        => [],
		];

		$payment_id = edd_insert_payment( $payment_data );

		if ( ! $payment_id ) {
			return [
				'status'  => 'error',
				'message' => 'Failed to create purchase. Please check your parameters and try again.',
			];
		}

		$payment = new \EDD_Payment( $payment_id );
		
		// Auto-generate license keys for EDD Software Licensing.
		$this->auto_generate_license_keys( $payment_id, $payment_data );
		
		// Setup license status management for refunds/revokes.
		$this->setup_license_status_management( $payment_id );

		// Get generated license keys.
		$license_keys = $this->get_license_keys_for_payment( $payment_id );

		$purchase_data = [
			'purchase_id'    => $payment_id,
			'purchase_key'   => $payment->key,
			'customer_email' => $payment->email,
			'customer_id'    => $payment->customer_id,
			'user_id'        => $payment->user_id,
			'first_name'     => $payment->first_name,
			'last_name'      => $payment->last_name,
			'total_amount'   => $payment->total,
			'subtotal'       => $payment->subtotal,
			'tax'            => $payment->tax,
			'currency'       => $payment->currency,
			'status'         => $payment->status,
			'payment_method' => $payment->gateway,
			'date'           => $payment->date,
			'downloads'      => array_map(
				function( $item ) {
					return [
						'download_id' => $item['id'],
						'name'        => $item['name'],
						'price'       => $item['price'],
						'quantity'    => isset( $item['quantity'] ) ? $item['quantity'] : 1,
					];
				},
				$payment->cart_details 
			),
			'license_keys'   => $license_keys,
		];

		return [
			'status'   => 'success',
			'message'  => 'Purchase created successfully',
			'purchase' => $purchase_data,
		];
	}
	
	/**
	 * Auto-generate license keys for purchased products
	 *
	 * @param int   $payment_id    The payment ID.
	 * @param array $payment_data  The payment data.
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function auto_generate_license_keys( $payment_id, $payment_data ) {
		// Check if EDD Software Licensing is available.
		if ( ! function_exists( 'edd_software_licensing' ) || ! class_exists( 'EDD_Software_Licensing' ) || ! class_exists( 'EDD_SL_License' ) ) {
			return;
		}
		
		if ( empty( $payment_data['cart_details'] ) ) {
			return;
		}
		
		foreach ( $payment_data['cart_details'] as $cart_index => $cart_item ) {
			$download_id = isset( $cart_item['id'] ) ? intval( $cart_item['id'] ) : 0;
			
			if ( empty( $download_id ) ) {
				continue;
			}
			
			// Get price ID if available.
			$price_id = null;
			if ( isset( $cart_item['item_number']['options']['price_id'] ) ) {
				$price_id = $cart_item['item_number']['options']['price_id'];
			}
			
			// Check if license already exists.
			$existing_license = edd_software_licensing()->get_license_by_purchase( $payment_id, $download_id, $cart_index );
			
			if ( ! empty( $existing_license ) ) {
				continue;
			}
			
			// Create license.
			/**
			 * EDD Software License instance.
			 *
			 * @var \EDD_SL_License $license
			 */
			$license = new \EDD_SL_License();
			$license->create( $download_id, $payment_id, $price_id, $cart_index );
		}
	}
	
	/**
	 * Setup license status management for refunds and revokes
	 *
	 * @param int $payment_id The payment ID.
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function setup_license_status_management( $payment_id ) {
		// Check if EDD Software Licensing is available.
		if ( ! function_exists( 'edd_software_licensing' ) || ! class_exists( 'EDD_Software_Licensing' ) ) {
			return;
		}
		
		// Hook into payment status changes to handle license status updates.
		add_action( 'edd_update_payment_status', [ $this, 'handle_license_status_on_payment_change' ], 10, 3 );
	}
	
	/**
	 * Handle license status changes when payment status changes
	 *
	 * @param int    $payment_id   The payment ID.
	 * @param string $new_status   The new payment status.
	 * @param string $old_status   The old payment status.
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_license_status_on_payment_change( $payment_id, $new_status, $old_status ) {
		// Only process if EDD Software Licensing is available.
		if ( ! function_exists( 'edd_software_licensing' ) || ! class_exists( 'EDD_SL_License' ) ) {
			return;
		}
		
		// Handle revoked and refunded statuses - both should disable licenses.
		if ( 'revoked' === $new_status || 'refunded' === $new_status ) {
			$this->disable_licenses_for_payment( $payment_id );
		}
	}
	
	/**
	 * Disable all licenses associated with a payment
	 *
	 * @param int $payment_id The payment ID.
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function disable_licenses_for_payment( $payment_id ) {
		// Check if EDD Software Licensing functions are available.
		if ( ! function_exists( 'edd_software_licensing' ) || ! class_exists( 'EDD_SL_License' ) ) {
			return;
		}
		
		// Get all licenses for this payment.
		$licenses = edd_software_licensing()->get_licenses_of_purchase( $payment_id );
		
		if ( empty( $licenses ) ) {
			return;
		}
		
		foreach ( $licenses as $license_data ) {
			if ( ! isset( $license_data->ID ) ) {
				continue;
			}
			
			/**
			 * EDD Software License instance.
			 *
			 * @var \EDD_SL_License $license
			 */
			$license = new \EDD_SL_License( $license_data->ID );
			
			// Only update if license is not already disabled.
			if ( 'disabled' !== $license->status ) {
				$license->update_meta( 'status', 'disabled' );
				$license->status = 'disabled';
				
				// Trigger action for other plugins to hook into.
				do_action( 'edd_sl_license_disabled', $license->ID, $payment_id );
			}
		}
	}
	
	/**
	 * Get license keys for a payment
	 *
	 * @param int $payment_id The payment ID.
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_license_keys_for_payment( $payment_id ) {
		// Check if EDD Software Licensing is available.
		if ( ! function_exists( 'edd_software_licensing' ) || ! class_exists( 'EDD_SL_License' ) ) {
			return [];
		}
		
		// Get all licenses for this payment.
		$licenses = edd_software_licensing()->get_licenses_of_purchase( $payment_id );
		
		if ( empty( $licenses ) ) {
			return [];
		}
		
		$license_data = [];
		foreach ( $licenses as $license ) {
			if ( ! isset( $license->ID ) ) {
				continue;
			}
			
			/**
			 * EDD Software License instance.
			 *
			 * @var \EDD_SL_License $license_obj
			 */
			$license_obj = new \EDD_SL_License( $license->ID );
			
			$license_data[] = [
				'license_id'  => $license_obj->ID,
				'license_key' => $license_obj->key,
				'status'      => $license_obj->status,
				'download_id' => $license_obj->download_id,
			];
		}
		
		return $license_data;
	}
}

CreatePurchase::get_instance();
