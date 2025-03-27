<?php
/**
 * Omnisend Tracking transformer
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Transformers\Events\Components;

use Omnisend\SDK\V1\Events\Components\Tracking as OmnisendTracking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TrackingTransformer
 */
class TrackingTransformer {
	private const TRACKING_CODE_SUPPORTED_EVENTS = array(
		'paid for order',
		'order fulfilled',
		'order refunded',
	);

	/**
	 * Transform SureCart tracking object to OmnisendTracking
	 *
	 * @param ?object $tracking
	 * @param string  $event_name
	 *
	 * @return ?OmnisendTracking
	 */
	public function transform_tracking( ?object $tracking, string $event_name ): ?OmnisendTracking {
		if ( ! $tracking ) {
			return null;
		}

		$omnisend_tracking = new OmnisendTracking();

		$omnisend_tracking->set_courier_title( $tracking->courier_name );
		$omnisend_tracking->set_courier_url( $tracking->url );

		if ( in_array( $event_name, self::TRACKING_CODE_SUPPORTED_EVENTS ) ) {
			$omnisend_tracking->set_code( $tracking->number );
		}

		return $omnisend_tracking;
	}
}
