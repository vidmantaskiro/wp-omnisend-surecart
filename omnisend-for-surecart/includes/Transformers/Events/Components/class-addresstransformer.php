<?php
/**
 * Omnisend Discount transformer
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Transformers\Events\Components;

use Omnisend\SDK\V1\Events\Components\Address as OmnisendAddress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AddressTransformer
 */
class AddressTransformer {
	/**
	 * Transforms SureCart address to Omnisend address
	 *
	 * @param ?object $shipping_address
	 * @param ?object $billing_address
	 * @param ?string $first_name
	 * @param ?string $last_name
	 *
	 * @return ?OmnisendAddress
	 */
	public function transform_address(
		?object $shipping_address,
		?object $billing_address,
		?string $first_name,
		?string $last_name
	): ?OmnisendAddress {
		if ( ! $shipping_address || ! $billing_address ) {
			return null;
		}

		$omnisend_address = new OmnisendAddress();

		$omnisend_address->set_billing_address_1( $billing_address->line_1 );
		$omnisend_address->set_billing_address_2( $billing_address->line_2 );
		$omnisend_address->set_billing_city( $billing_address->city );
		$omnisend_address->set_billing_country( $billing_address->country );
		$omnisend_address->set_billing_first_name( $first_name );
		$omnisend_address->set_billing_last_name( $last_name );
		$omnisend_address->set_billing_state( $billing_address->state );
		$omnisend_address->set_billing_zip( $billing_address->postal_code );

		$omnisend_address->set_shipping_address_1( $shipping_address->line_1 );
		$omnisend_address->set_shipping_address_2( $shipping_address->line_2 );
		$omnisend_address->set_shipping_city( $shipping_address->city );
		$omnisend_address->set_shipping_country( $shipping_address->country );
		$omnisend_address->set_shipping_first_name( $first_name );
		$omnisend_address->set_shipping_last_name( $last_name );
		$omnisend_address->set_shipping_state( $shipping_address->state );
		$omnisend_address->set_shipping_zip( $shipping_address->postal_code );

		return $omnisend_address;
	}
}
