<?php
/**
 * Omnisend checkout transformer
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Transformers\Events;

use Omnisend\SDK\V1\Event as OmnisendEvent;
use Omnisend\SDK\V1\Events\StartedCheckout as OmnisendCheckout;
use Omnisend\SureCartAddon\Transformers\ContactTransformer;
use Omnisend\SureCartAddon\Converter\ValueConverter;
use Omnisend\SureCartAddon\Transformers\Events\Components\LineItemTransformer;
use SureCart\Models\Checkout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CheckoutTransformer
 */
class CheckoutTransformer {
	/**
	 * @var ValueConverter
	 */
	private $value_converter;

	/**
	 * @var LineItemTransformer
	 */
	private $line_item_transformer;

	/**
	 * @var ContactTransformer
	 */
	private $contact_transformer;

	public function __construct() {
		$this->value_converter       = new ValueConverter();
		$this->line_item_transformer = new LineItemTransformer();
		$this->contact_transformer   = new ContactTransformer();
	}

	/**
	 * Transforms checkout data to Omnisend checkout event
	 *
	 * @param Checkout $checkout_data
	 *
	 * @return ?OmnisendEvent
	 */
	public function transform_checkout( Checkout $checkout_data ): ?OmnisendEvent {
		$email = $checkout_data->getAttribute( 'inherited_email' );

		if ( ! $email ) {
			return null;
		}

		$properties = $this->get_omnisend_properties( $checkout_data );
		$contact    = $this->contact_transformer->transform_contact_for_event( $email, ContactTransformer::ADD_CONTACT_ID );

		$omnisend_event = new OmnisendEvent();

		$omnisend_event->set_event_version( '' );
		$omnisend_event->set_recommended_event( $properties );
		$omnisend_event->set_contact( $contact );

		return $omnisend_event;
	}

	/**
	 * Gets Omnisend event properties
	 *
	 * @param Checkout $checkout_data
	 *
	 * @return OmnisendCheckout
	 */
	private function get_omnisend_properties( Checkout $checkout_data ): OmnisendCheckout {
		$omnisend_checkout = new OmnisendCheckout();

		$items = $checkout_data->getAttribute( 'line_items' )->data;

		$omnisend_checkout = $this->add_order_data( $omnisend_checkout, $checkout_data );
		$omnisend_checkout = $this->add_checkout_items( $omnisend_checkout, $items );

		return $omnisend_checkout;
	}

	/**
	 * Adds basic event data to Omnisend event
	 *
	 * @param OmnisendCheckout $omnisend_checkout
	 * @param Checkout         $checkout_data
	 *
	 * @return OmnisendCheckout
	 */
	private function add_order_data( OmnisendCheckout $omnisend_checkout, Checkout $checkout_data ): OmnisendCheckout {
		$omnisend_checkout->set_abandoned_checkout_url( $this->get_checkout_url( $checkout_data ) );
		$omnisend_checkout->set_cart_id( $checkout_data->getAttribute( 'id' ) );
		$omnisend_checkout->set_currency( strtoupper( $checkout_data->getAttribute( 'currency' ) ) );
		$omnisend_checkout->set_value( $this->value_converter->convert_price( $checkout_data->getAttribute( 'total_amount' ) ) );

		return $omnisend_checkout;
	}

	/**
	 * Adds cart items to Omnisend event
	 *
	 * @param OmnisendCheckout $omnisend_checkout
	 * @param array            $line_items
	 *
	 * @return OmnisendCheckout
	 */
	private function add_checkout_items( OmnisendCheckout $omnisend_checkout, array $line_items ): OmnisendCheckout {
		foreach ( $line_items as $item ) {
			$omnisend_item = $this->line_item_transformer->transform_item( $item );
			$omnisend_checkout->add_line_item( $omnisend_item );
		}

		return $omnisend_checkout;
	}

	/**
	 * Get checkout url from SureCart checkout metadata
	 *
	 * @param Checkout $checkout_data
	 *
	 * @return string
	 */
	private function get_checkout_url( Checkout $checkout_data ): string {
		$meta_data = $checkout_data->getAttribute( 'metadata' );

		if ( empty( $meta_data ) || ! property_exists( $meta_data, 'page_url' ) ) {
			return '';
		}

		return $meta_data->page_url;
	}
}
