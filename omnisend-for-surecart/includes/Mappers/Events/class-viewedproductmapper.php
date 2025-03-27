<?php
/**
 * Omnisend viewed product event mapper
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Mappers\Events;

use SureCart\Models\Product;
use Omnisend\SDK\V1\Product as OmnisendProduct;
use Omnisend\SureCartAddon\Converter\ValueConverter;
use Omnisend\SureCartAddon\Transformers\ContactTransformer;
use Omnisend\SureCartAddon\Transformers\Events\Components\CategoryTransformer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ViewedProductMapper
 */
class ViewedProductMapper {
	/**
	 * @var ValueConverter $value_converter
	 */
	private $value_converter;

	/**
	 * @var CategoryTransformer $category_transformer
	 */
	private $category_transformer;

	public function __construct() {
		$this->value_converter      = new ValueConverter();
		$this->category_transformer = new CategoryTransformer();
	}

	/**
	 * Maps Omnisend "Viewed Product" event
	 *
	 * @param Product $product
	 *
	 * @return ?array
	 */
	public function map_event( Product $product ): ?array {
		if ( ! isset( $_COOKIE[ ContactTransformer::OMNISEND_CONTACT_COOKIE ] ) ) {
			return null;
		}

		$data = array(
			'track',
			'viewed product',
			array(
				'origin'       => 'api',
				'eventVersion' => 'v4',
				'contact'      => array(
					'id'   => sanitize_text_field(
						wp_unslash( $_COOKIE[ ContactTransformer::OMNISEND_CONTACT_COOKIE ] )
					),
					'tags' => array(
						'source: surecart',
					),
				),
				'properties'   => array(
					'product' => $this->get_product_array( $product ),
				),
			),

		);

		return $data;
	}

	/**
	 * Maps SureCart product to Omnisend Product array
	 *
	 * @param Product $product
	 *
	 * @return array
	 */
	private function get_product_array( Product $product ): array {
		$main_image = $product->getFeaturedImageAttribute() ? $product->getFeaturedImageAttribute()->attributes()->src : null;

		return array(
			'categories' => $this->get_product_categories( $product ),
			'currency'   => strtoupper( $product->getAttribute( 'metrics' )->currency ),
			'id'         => $product->getAttribute( 'id' ),
			'imageUrl'   => $main_image,
			'price'      => $this->value_converter->convert_price( (int) $product->getAttribute( 'metrics' )->min_price_amount ),
			'title'      => $product->getAttribute( 'name' ),
			'status'     => $this->get_product_stock_status( $product ),
			'url'        => $product->getPermalinkAttribute(),
		);
	}

	/**
	 * Gets product stock status
	 *
	 * @param Product $product
	 *
	 * @return string
	 */
	private function get_product_stock_status( Product $product ): string {
		if ( $product->getHasUnlimitedStockAttribute() ) {
			return OmnisendProduct::STATUS_IN_STOCK;
		}

		return $product->getInStockAttribute() ? OmnisendProduct::STATUS_IN_STOCK : OmnisendProduct::STATUS_OUT_OF_STOCK;
	}

	/**
	 * Gets product categories
	 *
	 * @param Product $product
	 *
	 * @return array
	 */
	private function get_product_categories( Product $product ): array {
		$collections = $product->getAttribute( 'product_collections' )->data;
		$categories  = array();

		foreach ( $collections as $collection ) {
			$categories[] = $this->category_transformer->transform_category( $collection )->to_array();
		}

		return $categories;
	}
}
