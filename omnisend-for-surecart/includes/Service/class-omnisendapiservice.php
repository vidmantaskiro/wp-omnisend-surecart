<?php
/**
 * Omnisend Api service
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Service;

use Omnisend\SureCartAddon\Transformers\CategoryTransformer;
use Omnisend\SureCartAddon\Transformers\ProductTransformer;
use Omnisend\SureCartAddon\Transformers\ContactTransformer;
use Omnisend\SureCartAddon\Transformers\Events\OrderTransformer;
use Omnisend\SureCartAddon\Transformers\Events\CheckoutTransformer;
use Omnisend\SureCartAddon\Transformers\Events\CartTransformer;
use Omnisend\SureCartAddon\Mappers\Events\ViewedProductMapper;
use Omnisend\SureCartAddon\Service\SureCartModelService;
use Omnisend\SureCartAddon\Provider\OmnisendSettingsProvider;
use Omnisend\SDK\V1\Omnisend;
use Omnisend\SDK\V1\Options;
use SureCart\Models\Product;
use SureCart\Models\ProductCollection;
use SureCart\Models\Customer;
use SureCart\Models\Checkout;
use SureCart\Models\Fulfillment;
use SureCart\Models\Refund;
use SureCart\Models\LineItem;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OmnisendApiService
 */
class OmnisendApiService {
	private const STARTED_CHECKOUT_COOKIE = 'omnisend-sc-checkout-event';

	/**
	 * @var CategoryTransformer $category_transformer
	 */
	private $category_transformer;

	/**
	 * @var ProductTransformer $product_transformer
	 */
	private $product_transformer;

	/**
	 * @var OrderTransformer $order_transformer
	 */
	private $order_transformer;

	/**
	 * @var ContactTransformer $contact_transformer
	 */
	private $contact_transformer;

	/**
	 * @var CheckoutTransformer $checkout_transformer
	 */
	private $checkout_transformer;

	/**
	 * @var CartTransformer $cart_transformer
	 */
	private $cart_transformer;

	/**
	 * @var ViewedProductMapper $viewed_product_mapper
	 */
	private $viewed_product_mapper;

	/**
	 * @var SureCartModelService $sure_cart_service
	 */
	private $sure_cart_service;

	/**
	 * @var Client $client
	 */
	private $client;

	public function __construct() {
		$options = new Options();
		$options->set_origin( 'surecart' );

		$this->category_transformer  = new CategoryTransformer();
		$this->product_transformer   = new ProductTransformer();
		$this->order_transformer     = new OrderTransformer();
		$this->contact_transformer   = new ContactTransformer();
		$this->checkout_transformer  = new CheckoutTransformer();
		$this->cart_transformer      = new CartTransformer();
		$this->viewed_product_mapper = new ViewedProductMapper();
		$this->sure_cart_service     = new SureCartModelService();
		$this->client                = Omnisend::get_client(
			OMNISEND_SURECART_ADDON_NAME,
			OMNISEND_SURECART_ADDON_VERSION,
			$options
		);

		add_action( 'surecart/models/productcollection/created', array( $this, 'category_created' ) );
		add_action( 'surecart/models/productcollection/updated', array( $this, 'category_updated' ) );
		add_action( 'surecart/models/productcollection/deleted', array( $this, 'category_deleted' ) );

		add_action( 'surecart/models/product/updated', array( $this, 'product_updated' ) );
		add_action( 'surecart/models/product/deleted', array( $this, 'product_deleted' ) );

		add_action( 'surecart/models/customer/created', array( $this, 'customer_created' ) );

		add_action( 'surecart/checkout_confirmed', array( $this, 'order_created' ) );
		add_action( 'surecart/models/fulfillment/created', array( $this, 'order_fulfilled' ) );
		add_action( 'surecart/models/refund/created', array( $this, 'order_refunded' ) );
		add_action( 'surecart/models/checkout/cancelled', array( $this, 'order_canceled' ) );
		add_action( 'surecart/models/checkout/manually_paid', array( $this, 'order_paid' ) );

		add_action( 'surecart/models/checkout/created', array( $this, 'checkout_item_added' ) );
		add_action( 'surecart/models/lineitem/created', array( $this, 'item_added' ) );
		add_action( 'surecart/models/checkout/updated', array( $this, 'checkout_started' ) );
		add_action( 'wp', array( $this, 'set_cookie_for_started_checkout' ) );

		add_action( 'wp_footer', array( $this, 'product_viewed' ) );

		add_action( 'wp', array( $this, 'connect_store' ) );
	}

	/**
	 * Omnisend action when SureCart category is created
	 *
	 * @param ProductCollection $collection
	 *
	 * @return void
	 */
	public function category_created( ProductCollection $collection ): void {
		$omnisend_data = $this->category_transformer->transform_category( $collection );

		if ( empty( $omnisend_data ) ) {
			return;
		}

		$this->client->create_category( $omnisend_data );
	}

	/**
	 * Omnisend action when SureCart category is updated
	 *
	 * @param ProductCollection $collection
	 *
	 * @return void
	 */
	public function category_updated( ProductCollection $collection ): void {
		$omnisend_data = $this->category_transformer->transform_category( $collection );

		if ( empty( $omnisend_data ) ) {
			return;
		}

		$collection_id = $collection->getAttribute( 'id' );
		$is_sent       = $this->client->get_category_by_id( $collection_id );
		$is_sent       = ! $is_sent->get_wp_error()->has_errors();

		if ( ! $is_sent ) {
			$this->client->create_category( $omnisend_data );

			return;
		}

		$this->client->update_category( $omnisend_data );
	}

	/**
	 * Omnisend action when SureCart category is deleted
	 *
	 * @param ProductCollection $collection
	 *
	 * @return void
	 */
	public function category_deleted( ProductCollection $collection ): void {
		$category_id = $collection->getAttribute( 'id' );
		$is_sent     = $this->client->get_category_by_id( $category_id );
		$is_sent     = ! $is_sent->get_wp_error()->has_errors();

		if ( ! $is_sent ) {
			return;
		}

		$this->client->delete_category_by_id( $category_id );
	}

	/**
	 * Omnisend action when SureCart Product is updated
	 *
	 * @param Product $product
	 *
	 * @return void
	 */
	public function product_updated( Product $product ): void {
		$omnisend_data = $this->product_transformer->transform_product( $product );

		if ( empty( $omnisend_data ) ) {
			return;
		}

		$product_id = $product->getAttribute( 'id' );
		$is_sent    = $this->client->get_product_by_id( $product_id );
		$is_sent    = ! $is_sent->get_wp_error()->has_errors();

		if ( ! $is_sent ) {
			$this->client->create_product( $omnisend_data );

			return;
		}

		$this->client->replace_product( $omnisend_data );
	}

	/**
	 * Omnisend action when SureCart Product is deleted
	 *
	 * @param Product $product
	 *
	 * @return void
	 */
	public function product_deleted( Product $product ): void {
		$id      = $product->getAttribute( 'id' );
		$is_sent = $this->client->get_product_by_id( $id );
		$is_sent = ! $is_sent->get_wp_error()->has_errors();

		if ( ! $is_sent ) {
			return;
		}

		$this->client->delete_product_by_id( $id );
	}

	/**
	 * Omnisend action when SureCart customer is created
	 *
	 * @param Customer $customer
	 *
	 * @return void
	 */
	public function customer_created( Customer $customer ): void {
		$omnisend_data = $this->contact_transformer->transform_contact( $customer );

		if ( empty( $omnisend_data ) ) {
			return;
		}

		$this->client->create_contact( $omnisend_data );
	}

	/**
	 * Omnisend action when SureCart order is placed
	 *
	 * @param Checkout $checkout
	 *
	 * @return void
	 */
	public function order_created( Checkout $checkout ): void {
		$order_id = $checkout->getAttribute( 'order' );
		$checkout = null;

		if ( ! is_string( $order_id ) ) {
			return;
		}

		$order = $this->sure_cart_service->get_single_order( $order_id );

		if ( empty( $order ) ) {
			return;
		}

		$omnisend_data = $this->order_transformer->transform_order( $order, 'placed order' );
		$this->client->send_customer_event( $omnisend_data );

		if ( $order->getAttribute( 'status' ) === 'paid' ) {
			$omnisend_data = $this->order_transformer->transform_order( $order, 'paid for order' );
			$this->client->send_customer_event( $omnisend_data );
		}

		$omnisend_data = $this->contact_transformer->transform_contact_by_order( $order );
		$this->client->save_contact( $omnisend_data );
	}

	/**
	 * Omnisend action when SureCart order is fulfilled
	 *
	 * @param Fulfillment $order
	 *
	 * @return void
	 */
	public function order_fulfilled( Fulfillment $order ): void {
		$order_id = $order->getAttribute( 'order' );
		$order    = $this->sure_cart_service->get_single_order( $order_id );

		if ( empty( $order ) ) {
			return;
		}

		$omnisend_data = $this->order_transformer->transform_order( $order, 'order fulfilled' );
		$this->client->send_customer_event( $omnisend_data );
	}

	/**
	 * Omnisend action when SureCart order is refunded
	 *
	 * @param Refund $refund
	 *
	 * @return void
	 */
	public function order_refunded( Refund $refund ): void {
		$refund = $this->sure_cart_service->get_refund_items( $refund->getAttribute( 'id' ) );
		$order  = $this->sure_cart_service->get_single_order( $refund->getAttribute( 'charge' )->getAttribute( 'checkout' )->order );

		if ( empty( $order ) ) {
			return;
		}

		$order->refund_items  = $refund->getAttribute( 'refund_items' )->data;
		$order->refund_amount = $refund->getAttribute( 'amount' );

		$omnisend_data = $this->order_transformer->transform_order( $order, 'order refunded' );
		$this->client->send_customer_event( $omnisend_data );
	}

	/**
	 * Omnisend action when SureCart order is canceled
	 *
	 * @param Checkout $order
	 *
	 * @return void
	 */
	public function order_canceled( Checkout $order ): void {
		$order_id = $order->getAttribute( 'order' )->id;
		$order    = $this->sure_cart_service->get_single_order( $order_id );

		if ( empty( $order ) ) {
			return;
		}

		$omnisend_data = $this->order_transformer->transform_order( $order, 'order canceled' );
		$this->client->send_customer_event( $omnisend_data );
	}

	/**
	 * Omnisend action when SureCart order is paid
	 *
	 * @param Checkout $order
	 *
	 * @return void
	 */
	public function order_paid( Checkout $order ): void {
		$order_id = $order->getAttribute( 'order' )->id;
		$order    = $this->sure_cart_service->get_single_order( $order_id );

		if ( empty( $order ) ) {
			return;
		}

		$omnisend_data = $this->order_transformer->transform_order( $order, 'paid for order' );
		$this->client->send_customer_event( $omnisend_data );
	}

	/**
	 * Omnisend action when SureCart checkout was loaded
	 *
	 * @param Checkout $checkout_data
	 *
	 * @return void
	 */
	public function checkout_started( Checkout $checkout_data ): void {
		if ( ! isset( $_COOKIE[ self::STARTED_CHECKOUT_COOKIE ] ) || $_COOKIE[ self::STARTED_CHECKOUT_COOKIE ] !== '1' ) {
			return;
		}

		setcookie( self::STARTED_CHECKOUT_COOKIE, '0', strtotime( '+1 hour' ), '/' );

		$omnisend_data = $this->checkout_transformer->transform_checkout( $checkout_data );

		if ( ! $omnisend_data ) {
			return;
		}

		$this->client->send_customer_event( $omnisend_data );
	}

	/**
	 * Omnisend action to make sure started checkout event isn't executed more than once
	 *
	 * @return void
	 */
	public function set_cookie_for_started_checkout(): void {
		if ( isset( $_COOKIE[ self::STARTED_CHECKOUT_COOKIE ] ) && $_COOKIE[ self::STARTED_CHECKOUT_COOKIE ] === '1' ) {
			return;
		}

		setcookie( self::STARTED_CHECKOUT_COOKIE, '1', strtotime( '+1 hour' ), '/' );
	}

	/**
	 * Omnisend action when SureCart creates checkout information from very first customer add to cart
	 *
	 * @param Checkout $checkout_data
	 *
	 * @return void
	 */
	public function checkout_item_added( Checkout $checkout_data ): void {
		$omnisend_data = $this->cart_transformer->transform_checkout( $checkout_data );

		if ( $omnisend_data === null ) {
			return;
		}

		$this->client->send_customer_event( $omnisend_data );
	}

	/**
	 * Omnisend action when SureCart customer adds item to cart
	 *
	 * @param LineItem $line_item
	 *
	 * @return void
	 */
	public function item_added( LineItem $line_item ): void {
		$omnisend_data = $this->cart_transformer->transform_item( $line_item );

		if ( $omnisend_data === null ) {
			return;
		}

		$this->client->send_customer_event( $omnisend_data );
	}

	/**
	 * Omnisend action when SureCart product is viewed
	 *
	 * @return void
	 */
	public function product_viewed(): void {
		if ( get_post_type() !== 'sc_product' ) {
			return;
		}

		$item_id = get_post_meta( get_the_ID(), 'sc_id', true );

		if ( ! is_string( $item_id ) || empty( $item_id ) ) {
			return;
		}

		$product = $this->sure_cart_service->get_single_product( $item_id );

		if ( ! $product ) {
			return;
		}

		$omnisend_data = $this->viewed_product_mapper->map_event( $product );

		if ( ! $omnisend_data ) {
			return;
		}

		$events_path = plugins_url( '../../assets/js/event-pusher.js', __FILE__ );

		wp_enqueue_script( 'omnisend-sc-event-script', $events_path, array(), OMNISEND_SURECART_ADDON_VERSION, true );
		wp_localize_script( 'omnisend-sc-event-script', 'event_data', $omnisend_data );
	}

	/**
	 * Connects to Omnisend platform
	 *
	 * @return void
	 */
	public function connect_store(): void {
		$is_connected = get_option( OmnisendSettingsProvider::STORE_CONNECTED_OPTION );

		if ( $is_connected === false ) {
			$this->client->connect_store( 'sureCart' );
			update_option( OmnisendSettingsProvider::STORE_CONNECTED_OPTION, 1 );
		}
	}
}
