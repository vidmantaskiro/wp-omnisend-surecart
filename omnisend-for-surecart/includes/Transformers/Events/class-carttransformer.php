<?php
/**
 * Omnisend cart transformer
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Transformers\Events;

use Omnisend\SDK\V1\Events\AddedProductToCart as OmnisendCart;
use Omnisend\SDK\V1\Event as OmnisendEvent;
use Omnisend\SureCartAddon\Converter\ValueConverter;
use Omnisend\SureCartAddon\Transformers\ContactTransformer;
use Omnisend\SureCartAddon\Transformers\Events\Components\LineItemTransformer;
use SureCart\Models\Checkout;
use SureCart\Models\LineItem;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CartTransformer
 */
class CartTransformer {
	/**
	 * @var ValueConverter $value_converter
	 */
	private $value_converter;

	/**
	 * @var ContactTransformer $contact_transformer
	 */
	private $contact_transformer;

	/**
	 * @var LineItemTransformer $line_item_transformer
	 */
	private $line_item_transformer;

	public function __construct() {
		$this->value_converter       = new ValueConverter();
		$this->contact_transformer   = new ContactTransformer();
		$this->line_item_transformer = new LineItemTransformer();
	}

	/**
	 * Transforms checkout data to Omnisend cart event
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

		$contact    = $this->contact_transformer->transform_contact_for_event( $email, ContactTransformer::ADD_CONTACT_ID );
		$properties = $this->get_event_properties( $checkout_data );

		$omnisend_event = new OmnisendEvent();

		$omnisend_event->set_event_version( '' );
		$omnisend_event->set_recommended_event( $properties );
		$omnisend_event->set_contact( $contact );

		return $omnisend_event;
	}

	/**
	 * Transforms SureCart line item to Omnisend Cart event
	 *
	 * @param LineItem $line_item
	 *
	 * @return ?OmnisendEvent
	 */
	public function transform_item( LineItem $line_item ): ?OmnisendEvent {
		$checkout_data = $line_item->getAttribute( 'checkout' );
		$added_item_id = $line_item->getAttribute( 'id' );
		$email         = $checkout_data->getAttribute( 'inherited_email' );

		if ( ! $email ) {
			return null;
		}

		$contact    = $this->contact_transformer->transform_contact_for_event( $email, ContactTransformer::ADD_CONTACT_ID );
		$properties = $this->get_event_properties( $checkout_data, $added_item_id );

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
	 * @param ?string  $added_item_id
	 *
	 * @return OmnisendCart
	 */
	private function get_event_properties( Checkout $checkout_data, ?string $added_item_id = null ): OmnisendCart {
		$omnisend_cart = new OmnisendCart();

		$items = $checkout_data->getAttribute( 'line_items' )->data;

		$omnisend_cart = $this->add_order_data( $omnisend_cart, $checkout_data );
		$omnisend_cart = $this->add_cart_items( $omnisend_cart, $items, $added_item_id );

		return $omnisend_cart;
	}

	/**
	 * Adds basic event data to Omnisend event
	 *
	 * @param OmnisendCart $omnisend_cart
	 * @param Checkout     $checkout_data
	 *
	 * @return OmnisendCart
	 */
	private function add_order_data( OmnisendCart $omnisend_cart, Checkout $checkout_data ): OmnisendCart {
		$omnisend_cart->set_abandoned_checkout_url( $this->get_checkout_url( $checkout_data ) );
		$omnisend_cart->set_cart_id( $checkout_data->getAttribute( 'id' ) );
		$omnisend_cart->set_currency( strtoupper( $checkout_data->getAttribute( 'currency' ) ) );
		$omnisend_cart->set_value( $this->value_converter->convert_price( $checkout_data->getAttribute( 'total_amount' ) ) );

		return $omnisend_cart;
	}

	/**
	 * Adds cart items to Omnisend cart event
	 *
	 * @param OmnisendCart $omnisend_cart
	 * @param array        $line_items
	 * @param ?string      $added_item_id
	 *
	 * @return OmnisendCart
	 */
	private function add_cart_items( OmnisendCart $omnisend_cart, array $line_items, ?string $added_item_id ): OmnisendCart {
		foreach ( $line_items as $item ) {
			if ( $item->getAttribute( 'total_amount' ) === 0 ) {
				continue;
			}

			$omnisend_item = $this->line_item_transformer->transform_item( $item );

			if ( $added_item_id === $item->getAttribute( 'id' ) || count( $line_items ) === 1 ) {
				$omnisend_cart->set_added_item( $omnisend_item );
			}

			$omnisend_cart->add_line_item( $omnisend_item );
		}

		return $omnisend_cart;
	}

	/**
	 * Gets checkout URL
	 *
	 * @param Checkout $checkout_data
	 *
	 * @return string
	 */
	private function get_checkout_url( Checkout $checkout_data ): string {
		$meta_data = $checkout_data->getAttribute( 'metadata' );

		if ( empty( $meta_data ) || ! property_exists( $meta_data, 'page_url' ) ) {
			return $checkout_data->getAttribute( 'portal_url' );
		}

		return $meta_data->page_url;
	}
}
