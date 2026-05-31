<?php
/**
 * CreateCoupon.
 * php version 5.6
 *
 * @category CreateCoupon
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Amelia\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use AmeliaBooking\Domain\Factory\Coupon\CouponFactory;
use AmeliaBooking\Application\Services\Coupon\CouponApplicationService;

/**
 * CreateCoupon
 *
 * @category CreateCoupon
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateCoupon extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'Amelia';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'amelia_create_coupon';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Coupon', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];

		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id         User ID.
	 * @param int   $automation_id   Automation ID.
	 * @param array $fields          Fields.
	 * @param array $selected_options Selected options.
	 *
	 * @return array|void
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		$coupon_code     = isset( $selected_options['coupon_code'] ) ? sanitize_text_field( $selected_options['coupon_code'] ) : '';
		$discount_type   = isset( $selected_options['discount_type'] ) ? sanitize_text_field( $selected_options['discount_type'] ) : 'percentage';
		$discount_value  = isset( $selected_options['discount_value'] ) ? floatval( $selected_options['discount_value'] ) : 0;
		$usage_limit     = isset( $selected_options['usage_limit'] ) ? intval( $selected_options['usage_limit'] ) : 1;
		$customer_limit  = isset( $selected_options['customer_limit'] ) ? intval( $selected_options['customer_limit'] ) : 1;
		$expiration_date = isset( $selected_options['expiration_date'] ) ? sanitize_text_field( $selected_options['expiration_date'] ) : '';
		$status          = isset( $selected_options['status'] ) ? sanitize_text_field( $selected_options['status'] ) : 'visible';
		$service_ids     = isset( $selected_options['service_ids'] ) ? $this->parse_comma_separated_ids( $selected_options['service_ids'] ) : [];
		$event_ids       = isset( $selected_options['event_ids'] ) ? $this->parse_comma_separated_ids( $selected_options['event_ids'] ) : [];
		$package_ids     = isset( $selected_options['package_ids'] ) ? $this->parse_comma_separated_ids( $selected_options['package_ids'] ) : [];
		$all_services    = isset( $selected_options['all_services'] ) ? (bool) intval( $selected_options['all_services'] ) : false;
		$all_events      = isset( $selected_options['all_events'] ) ? (bool) intval( $selected_options['all_events'] ) : false;
		$all_packages    = isset( $selected_options['all_packages'] ) ? (bool) intval( $selected_options['all_packages'] ) : false;

		
		// Validate required fields.
		if ( empty( $coupon_code ) ) {
			return [
				'status'  => 'error',
				'message' => 'Coupon code is required.',
			];
		}

		if ( $discount_value <= 0 ) {
			return [
				'status'  => 'error',
				'message' => 'Discount value must be greater than 0.',
			];
		}

		// Check if Amelia classes exist.
		if ( ! class_exists( 'AmeliaBooking\Domain\Factory\Coupon\CouponFactory' ) ) {
			return [
				'status'  => 'error',
				'message' => 'Amelia CouponFactory class not found.',
			];
		}

		try {
			// Check if AMELIA_PATH is defined.
			if ( ! defined( 'AMELIA_PATH' ) ) {
				return [
					'status'  => 'error',
					'message' => 'Amelia plugin is not active or AMELIA_PATH constant is not defined.',
				];
			}

			// Get Amelia container.
			$container = require AMELIA_PATH . '/src/Infrastructure/ContainerConfig/container.php';

			// Get repositories and services.
			$service_repository = $container->get( 'domain.bookable.service.repository' );
			$package_repository = $container->get( 'domain.bookable.package.repository' );
			$event_service      = $container->get( 'application.booking.event.service' );
			$coupon_service     = $container->get( 'application.coupon.service' );
			$coupon_repository  = $container->get( 'domain.coupon.repository' );

			// Prepare coupon data.
			$coupon_data = [
				'code'          => $coupon_code,
				'status'        => $status,
				'limit'         => $usage_limit,
				'customerLimit' => $customer_limit,
				'used'          => 0,
				'allServices'   => $all_services,
				'allEvents'     => $all_events,
				'allPackages'   => $all_packages,
			];

			// Set discount based on type.
			if ( 'percentage' === $discount_type ) {
				$coupon_data['discount']  = $discount_value;
				$coupon_data['deduction'] = 0;
			} else {
				$coupon_data['discount']  = 0;
				$coupon_data['deduction'] = $discount_value;
			}

			// Set expiration date if provided.
			if ( ! empty( $expiration_date ) ) {
				$coupon_data['expirationDate'] = $expiration_date;
			}

			// Create coupon using factory.
			$coupon = CouponFactory::create( $coupon_data );

			// Fetch and set service collections.
			if ( ! empty( $service_ids ) ) {
				$services = $service_repository->getByCriteria( [ 'services' => $service_ids ] );
				$coupon->setServiceList( $services );
			} else {
				if ( class_exists( '\AmeliaBooking\Domain\Collection\Collection' ) ) {
					$coupon->setServiceList( new \AmeliaBooking\Domain\Collection\Collection() );
				}
			}

			// Fetch and set event collections.
			if ( ! empty( $event_ids ) ) {
				$events = $event_service->getEventsByIds( $event_ids, [] );
				$coupon->setEventList( $events );
			} else {
				if ( class_exists( '\AmeliaBooking\Domain\Collection\Collection' ) ) {
					$coupon->setEventList( new \AmeliaBooking\Domain\Collection\Collection() );
				}
			}

			// Fetch and set package collections.
			if ( ! empty( $package_ids ) ) {
				$packages = $package_repository->getByCriteria( [ 'packages' => $package_ids ] );
				$coupon->setPackageList( $packages );
			} else {
				if ( class_exists( '\AmeliaBooking\Domain\Collection\Collection' ) ) {
					$coupon->setPackageList( new \AmeliaBooking\Domain\Collection\Collection() );
				}
			}

			// Start transaction.
			$coupon_repository->beginTransaction();

			// Add coupon using application service.
			$coupon_id = $coupon_service->add( $coupon );

			if ( $coupon_id ) {
				// Commit transaction.
				$coupon_repository->commit();
				
				return [
					'status'         => 'success',
					'message'        => 'Coupon created successfully',
					'coupon_id'      => $coupon_id,
					'coupon_code'    => $coupon_code,
					'discount_type'  => $discount_type,
					'discount_value' => $discount_value,
				];
			} else {
				// Rollback transaction.
				$coupon_repository->rollback();

				return [
					'status'  => 'error',
					'message' => 'Failed to create coupon',
				];
			}       
		} catch ( Exception $e ) {
			// Rollback transaction if it was started.
			if ( isset( $coupon_repository ) ) {
				$coupon_repository->rollback();
			}

			return [
				'status'  => 'error',
				'message' => 'An error occurred while creating the coupon: ' . $e->getMessage(),
			];
		}
	}

	/**
	 * Parse comma-separated IDs string into array of integers.
	 *
	 * @param string|array $ids Comma-separated string or array of IDs.
	 * @return array
	 */
	private function parse_comma_separated_ids( $ids ) {
		if ( is_array( $ids ) ) {
			return array_map( 'intval', array_filter( $ids ) );
		}

		if ( empty( $ids ) || ! is_string( $ids ) ) {
			return [];
		}

		// Split by comma and clean up.
		$ids_array = explode( ',', $ids );
		$ids_array = array_map( 'trim', $ids_array );
		$ids_array = array_filter( $ids_array );
		$ids_array = array_map( 'intval', $ids_array );

		// Remove any zero values.
		return array_filter( $ids_array );
	}
}

CreateCoupon::get_instance();
