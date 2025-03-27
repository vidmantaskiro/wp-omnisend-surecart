<?php
/**
 * Omnisend line item transformer
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Transformers\Events\Components;

use SureCart\Models\Product;
use SureCart\Models\LineItem;
use Omnisend\SDK\V1\Events\Components\LineItem as OmnisendLineItem;
use Omnisend\SureCartAddon\Converter\ValueConverter;
use Omnisend\SureCartAddon\Transformers\Events\Components\CategoryTransformer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LineItemTransformer
 */
class LineItemTransformer {
	/**
	 * @var ValueConverter
	 */
	private $value_converter;

	/**
	 * @var CategoryTransformer
	 */
	private $category_transformer;

	public function __construct() {
		$this->value_converter      = new ValueConverter();
		$this->category_transformer = new CategoryTransformer();
	}

	/**
	 * Transforms SureCart LineItem to Omnisend event LineItem
	 *
	 * @param LineItem $item
	 *
	 * @return OmnisendLineItem
	 */
	public function transform_item( LineItem $item ): OmnisendLineItem {
		$line_item = new OmnisendLineItem();

		$price      = $item->getAttribute( 'price' );
		$product    = $price->getAttribute( 'product' );
		$main_image = $product->getFeaturedImageAttribute() ? $product->getFeaturedImageAttribute()->attributes()->src : null;

		$line_item->set_description( $product->getAttribute( 'description' ) );
		$line_item->set_id( $item->getAttribute( 'id' ) );
		$line_item->set_title( $product->getAttribute( 'name' ) );
		$line_item->set_url( $product->getPermalinkAttribute() );
		$line_item->set_image_url( $main_image );
		$line_item->set_quantity( $item->getAttribute( 'quantity' ) );
		$line_item->set_sku( $product->getAttribute( 'sku' ) );

		$line_item->set_price( $this->value_converter->convert_price( $item->getAttribute( 'total_amount' ) ) );
		$line_item->set_strike_through_price( $this->value_converter->convert_price( $item->getAttribute( 'full_amount' ) ) );
		$line_item->set_discount( abs( $this->value_converter->convert_price( $item->getAttribute( 'discount_amount' ) ) ) );

		$line_item = $this->add_categories( $line_item, $product );

		if ( $item->getAttribute( 'variant' ) === null ) {
			$line_item->set_variant_id( $product->getAttribute( 'id' ) );
		} else {
			$line_item->set_variant_id( $item->getAttribute( 'variant' )->getAttribute( 'id' ) );
		}

		return $line_item;
	}

	/**
	 * Transforms SureCart LineItem to Omnisend order event LineItem
	 *
	 * @param LineItem $item
	 *
	 * @return OmnisendLineItem
	 */
	public function transform_order_item( LineItem $item ): OmnisendLineItem {
		$line_item = new OmnisendLineItem();

		$product    = $item->getAttribute( 'price' )->getAttribute( 'product' );
		$main_image = $product->getFeaturedImageAttribute() ? $product->getFeaturedImageAttribute()->attributes()->src : null;

		$line_item->set_description( $product->getAttribute( 'description' ) );
		$line_item->set_discount( abs( $this->value_converter->convert_price( (int) $item->getAttribute( 'discount_amount' ) ) ) );
		$line_item->set_id( $product->getAttribute( 'id' ) );
		$line_item->set_image_url( $main_image );
		$line_item->set_price( $this->value_converter->convert_price( (int) $item->getAttribute( 'total_amount' ) ) );
		$line_item->set_quantity( $item->getAttribute( 'quantity' ) );
		$line_item->set_sku( $product->getAttribute( 'sku' ) );
		$line_item->set_title( $product->getAttribute( 'name' ) );
		$line_item->set_url( $product->getPermalinkAttribute() );
		$line_item->set_variant_image_url( $main_image );
		$line_item->set_weight( $product->getAttribute( 'weight' ) );

		$line_item = $this->add_categories( $line_item, $product );

		if ( $item->getAttribute( 'variant' ) === null ) {
			$line_item->set_variant_id( $product->getAttribute( 'id' ) );
			$line_item->set_variant_title( $product->getAttribute( 'name' ) );

			return $line_item;
		}

		$line_item->set_variant_id( $item->getAttribute( 'variant' ) );
		$line_item->set_variant_title( $item->getAttribute( 'variant_options' )[0] ?? null );

		return $line_item;
	}

	/**
	 * Adds to Omnisend LineItem categories
	 *
	 * @param OmnisendLineItem $line_item
	 * @param Product          $product
	 *
	 * @return OmnisendLineItem
	 */
	private function add_categories( OmnisendLineItem $line_item, Product $product ): OmnisendLineItem {
		$collections = $product->getAttribute( 'product_collections' )->data;

		foreach ( $collections as $collection ) {
			$omnisend_category = $this->category_transformer->transform_category( $collection );
			$line_item->add_category( $omnisend_category );
		}

		return $line_item;
	}
}
