<?php
/**
 * Omnisend product transformer
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Transformers;

use Omnisend\SDK\V1\Product as OmnisendProduct;
use Omnisend\SDK\V1\ProductVariant as OmnisendVariant;
use Omnisend\SureCartAddon\Converter\ValueConverter;
use SureCart\Models\Product;
use SureCart\Models\Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ProductTransformer
 */
class ProductTransformer {
	/**
	 * @var ValueConverter $value_converter
	 */
	private $value_converter;

	public function __construct() {
		$this->value_converter = new ValueConverter();
	}

	/**
	 * Transforms SureCart products to Omnisend products
	 *
	 * @param array $products
	 *
	 * @return array
	 */
	public function transform_products( array $products ): array {
		$data = array();

		foreach ( $products as $product ) {
			$data[] = $this->transform_product( $product );
		}

		return $data;
	}

	/**
	 * Transforms SureCart product to Omnisend product
	 *
	 * @param Product $product
	 *
	 * @return OmnisendProduct
	 */
	public function transform_product( Product $product ): OmnisendProduct {
		$omnisend_product = new OmnisendProduct();

		$omnisend_product->set_created_at( $this->value_converter->convert_timestamp( (int) $product->getAttribute( 'created_at' ) ) );
		$omnisend_product->set_updated_at( $this->value_converter->convert_timestamp( (int) $product->getAttribute( 'updated_at' ) ) );
		$omnisend_product->set_currency( strtoupper( $product->getAttribute( 'metrics' )->currency ) );
		$omnisend_product->set_default_image_url( $this->get_default_image_url( $product ) );
		$omnisend_product->set_title( $product->getAttribute( 'name' ) );
		$omnisend_product->set_description( $product->getAttribute( 'description' ) );
		$omnisend_product->set_id( $product->getAttribute( 'id' ) );
		$omnisend_product->set_status( $this->get_product_stock_status( $product ) );
		$omnisend_product->set_url( $product->getPermalinkAttribute() );

		$omnisend_product = $this->add_variants( $omnisend_product, $product );
		$omnisend_product = $this->add_gallery_images( $omnisend_product, $product );
		$omnisend_product = $this->add_collections( $omnisend_product, $product );

		return $omnisend_product;
	}

	/**
	 * Adds SureCart product variants to Omnisend product
	 *
	 * @param OmnisendProduct $omnisend_product
	 * @param Product         $product
	 *
	 * @return OmnisendProduct
	 */
	private function add_variants( OmnisendProduct $omnisend_product, Product $product ): OmnisendProduct {
		$omnisend_variant = new OmnisendVariant();

		$variants = $product->getAttribute( 'variants' )->data;
		$data     = array();

		if ( count( $variants ) === 0 ) {
			$omnisend_variant->set_default_image_url( $this->get_default_image_url( $product ) );
			$omnisend_variant->set_description( $product->getAttribute( 'description' ) );
			$omnisend_variant->set_id( $product->getAttribute( 'id' ) );
			$omnisend_variant->set_price( $this->value_converter->convert_price( (int) $product->getAttribute( 'metrics' )->min_price_amount ) );
			$omnisend_variant->set_sku( $product->getAttribute( 'sku' ) );
			$omnisend_variant->set_status( $this->get_product_stock_status( $product ) );
			$omnisend_variant->set_title( $product->getAttribute( 'name' ) );
			$omnisend_variant->set_url( $product->getPermalinkAttribute() );

			$omnisend_product->add_variant( $omnisend_variant );

			return $omnisend_product;
		}

		foreach ( $variants as $variant ) {
			$omnisend_variant = new OmnisendVariant();

			$omnisend_variant->set_default_image_url( $this->get_default_variant_image_url( $variant ) );
			$omnisend_variant->set_description( $product->getAttribute( 'description' ) );
			$omnisend_variant->set_id( $variant->getAttribute( 'id' ) );
			$omnisend_variant->set_price( $this->value_converter->convert_price( (int) $variant->getAttribute( 'amount' ) ) );
			$omnisend_variant->set_sku( $variant->getAttribute( 'sku' ) );
			$omnisend_variant->set_status( $this->get_variant_stock_status( $product, $variant ) );
			$omnisend_variant->set_title( $product->getAttribute( 'name' ) . ' - ' . $variant->getAttribute( 'option_1' ) );
			$omnisend_variant->set_url( $product->getPermalinkAttribute() );

			$omnisend_product->add_variant( $omnisend_variant );
		}

		return $omnisend_product;
	}

	/**
	 * Gets SureCart product stock status
	 *
	 * @param Product $product
	 *
	 * @return string
	 */
	private function get_product_stock_status( Product $product ): string {
		if ( $product->getAttribute( 'archived' ) ) {
			return OmnisendProduct::STATUS_NOT_AVAILABLE;
		}

		if ( $product->getHasUnlimitedStockAttribute() ) {
			return OmnisendProduct::STATUS_IN_STOCK;
		}

		return $product->getInStockAttribute() ? OmnisendProduct::STATUS_IN_STOCK : OmnisendProduct::STATUS_OUT_OF_STOCK;
	}

	/**
	 * Gets SureCart product variant stock status
	 *
	 * @param Product $product
	 * @param Variant $variant
	 *
	 * @return string
	 */
	private function get_variant_stock_status( Product $product, Variant $variant ): string {
		if ( $product->getAttribute( 'archived' ) ) {
			return OmnisendProduct::STATUS_NOT_AVAILABLE;
		}

		if ( $product->getHasUnlimitedStockAttribute() ) {
			return OmnisendProduct::STATUS_IN_STOCK;
		}

		return ( $variant->getAttribute( 'available_stock' ) > 0 ) ? OmnisendProduct::STATUS_IN_STOCK : OmnisendProduct::STATUS_OUT_OF_STOCK;
	}

	/**
	 * Adds SureCart product image gallery to Omnisend product
	 *
	 * @param OmnisendProduct $omnisend_product
	 * @param Product         $product
	 *
	 * @return OmnisendProduct
	 */
	private function add_gallery_images( OmnisendProduct $omnisend_product, Product $product ): OmnisendProduct {
		$gallery = $product->getGalleryAttribute();

		if ( ! is_array( $gallery ) || count( $gallery ) <= 1 ) {
			return $omnisend_product;
		}

		$default_image_src = $this->get_default_image_url( $product );

		foreach ( $gallery as $image ) {
			$image_src = $image->attributes()->src;

			if ( $image_src == $default_image_src ) {
				continue;
			}

			$omnisend_product->add_image( $image_src );
		}

		return $omnisend_product;
	}

	/**
	 * Gets default image URL
	 *
	 * @param Product $product
	 *
	 * @return ?string
	 */
	private function get_default_image_url( Product $product ): ?string {
		$data = $product->getFeaturedImageAttribute();

		if ( ! $data ) {
			return null;
		}

		return $data->attributes()->src;
	}

	/**
	 * Gets default variant image URL
	 *
	 * @param Variant $variant
	 *
	 * @return ?string
	 */
	private function get_default_variant_image_url( Variant $variant ): ?string {
		$variant_image = property_exists( $variant->getAttribute( 'metadata' ), 'wp_media' ) ? $variant->getAttribute( 'metadata' )->wp_media : null;
		$variant_image = $variant_image ? wp_get_attachment_url( $variant_image ) : $variant_image;

		if ( ! $variant_image || ! is_string( $variant_image ) ) {
			return null;
		}

		return $variant_image;
	}

	/**
	 * Adds SureCart product collections to Omnisend product
	 *
	 * @param OmnisendProduct $omnisend_product
	 * @param Product         $product
	 *
	 * @return OmnisendProduct
	 */
	private function add_collections( OmnisendProduct $omnisend_product, Product $product ): OmnisendProduct {
		$collections = $product->getAttribute( 'product_collections' )->data;

		foreach ( $collections as $collection ) {
			$omnisend_product->add_category_id( $collection->getAttribute( 'id' ) );
		}

		return $omnisend_product;
	}
}
