<?php
/**
 * Omnisend Discount transformer
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Transformers\Events\Components;

use SureCart\Models\ProductCollection;
use Omnisend\SDK\V1\Events\Components\ProductCategory as OmnisendCategory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CategoryTransformer
 */
class CategoryTransformer {
	/**
	 * Transforms SureCart category to Omnisend category
	 *
	 * @param ?ProductCollection $category
	 *
	 * @return ?OmnisendCategory
	 */
	public function transform_category( ?ProductCollection $category ): ?OmnisendCategory {
		if ( ! $category ) {
			return null;
		}

		$omnisend_category = new OmnisendCategory();

		$omnisend_category->set_title( $category->getAttribute( 'name' ) );
		$omnisend_category->set_id( $category->getAttribute( 'id' ) );

		return $omnisend_category;
	}
}
