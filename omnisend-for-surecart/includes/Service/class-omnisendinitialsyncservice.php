<?php
/**
 * Omnisend Initial sync service
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Service;

use Omnisend\SureCartAddon\Transformers\CategoryTransformer;
use Omnisend\SureCartAddon\Transformers\ProductTransformer;
use Omnisend\SureCartAddon\Transformers\Events\OrderTransformer;
use Omnisend\SureCartAddon\Transformers\ContactTransformer;
use Omnisend\SureCartAddon\Service\SureCartModelService;
use Omnisend\SDK\V1\Batch;
use Omnisend\SDK\V1\Omnisend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OmnisendInitialSyncService
 */
class OmnisendInitialSyncService {
	private const SYNC_BATCH_LIMIT = 40;

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
	 * @var SureCartModelService $sure_cart_service
	 */
	private $sure_cart_service;

	/**
	 * @var Client $client
	 */
	private $client;

	public function __construct() {
		$this->category_transformer = new CategoryTransformer();
		$this->product_transformer  = new ProductTransformer();
		$this->order_transformer    = new OrderTransformer();
		$this->contact_transformer  = new ContactTransformer();
		$this->sure_cart_service    = new SureCartModelService();
		$this->client               = Omnisend::get_client(
			OMNISEND_SURECART_ADDON_NAME,
			OMNISEND_SURECART_ADDON_VERSION
		);
	}

	/**
	 * Process SureCart category sync to Omnisend
	 *
	 * @return void
	 */
	public function sync_all_categories(): void {
		$page          = 1;
		$omnisend_data = array();

		while ( true ) {
			$surecart_data = $this->sure_cart_service->get_categories_by_page( $page );
			++$page;

			if ( empty( $surecart_data ) ) {
				break;
			}

			$surecart_data = $this->category_transformer->transform_categories( $surecart_data );
			$omnisend_data = array_merge( $omnisend_data, $surecart_data );

			if ( count( $omnisend_data ) >= self::SYNC_BATCH_LIMIT ) {
				$this->send_batch( $omnisend_data, Batch::POST_METHOD );
				$omnisend_data = array();
			}
		}

		if ( count( $omnisend_data ) > 0 ) {
			$this->send_batch( $omnisend_data, Batch::POST_METHOD );
		}
	}

	/**
	 * Process SureCart product sync to Omnisend
	 *
	 * @return void
	 */
	public function sync_all_products(): void {
		$page          = 1;
		$omnisend_data = array();

		while ( true ) {
			$surecart_data = $this->sure_cart_service->get_products_by_page( $page );
			++$page;

			if ( empty( $surecart_data ) ) {
				break;
			}

			$surecart_data = $this->product_transformer->transform_products( $surecart_data );
			$omnisend_data = array_merge( $omnisend_data, $surecart_data );

			if ( count( $omnisend_data ) >= self::SYNC_BATCH_LIMIT ) {
				$this->send_batch( $omnisend_data, Batch::POST_METHOD );
				$omnisend_data = array();
			}
		}

		if ( count( $omnisend_data ) > 0 ) {
			$this->send_batch( $omnisend_data, Batch::POST_METHOD );
		}
	}

	/**
	 * Process SureCart order sync to Omnisend
	 *
	 * @return void
	 */
	public function sync_all_orders(): void {
		$page          = 1;
		$omnisend_data = array();

		while ( true ) {
			$surecart_data = $this->sure_cart_service->get_orders_by_page( $page );
			++$page;

			if ( empty( $surecart_data ) ) {
				break;
			}

			$surecart_data = $this->order_transformer->transform_orders( $surecart_data, 'placed order' );
			$omnisend_data = array_merge( $omnisend_data, $surecart_data );

			if ( count( $omnisend_data ) >= self::SYNC_BATCH_LIMIT ) {
				$this->send_batch( $omnisend_data, Batch::POST_METHOD );
				$omnisend_data = array();
			}
		}

		if ( count( $omnisend_data ) > 0 ) {
			$this->send_batch( $omnisend_data, Batch::POST_METHOD );
		}
	}

	/**
	 * Process SureCart customer sync to Omnisend
	 *
	 * @return void
	 */
	public function sync_all_customers(): void {
		$page          = 1;
		$omnisend_data = array();

		while ( true ) {
			$surecart_data = $this->sure_cart_service->get_customers_by_page( $page );
			++$page;

			if ( empty( $surecart_data ) ) {
				break;
			}

			$surecart_data = $this->contact_transformer->transform_contacts( $surecart_data );
			$omnisend_data = array_merge( $omnisend_data, $surecart_data );

			if ( count( $omnisend_data ) >= self::SYNC_BATCH_LIMIT ) {
				$this->send_batch( $omnisend_data, Batch::POST_METHOD );
				$omnisend_data = array();
			}
		}

		if ( count( $omnisend_data ) > 0 ) {
			$this->send_batch( $omnisend_data, Batch::POST_METHOD );
		}
	}

	/**
	 * Sends batch of items to Omnisend
	 *
	 * @param array  $data
	 * @param string $method
	 *
	 * @return void
	 */
	private function send_batch( array $data, string $method ): void {
		if ( empty( $data ) ) {
			return;
		}

		$batch = new Batch();
		$batch->set_items( $data );
		$batch->set_method( $method );

		$this->client->send_batch( $batch );
	}
}
