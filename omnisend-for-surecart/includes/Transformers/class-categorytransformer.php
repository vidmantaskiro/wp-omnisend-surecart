<?php
/**
 * Omnisend category transformer
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Transformers;

use Omnisend\SDK\V1\Category as OmnisendCategory;
use SureCart\Models\ProductCollection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CategoryTransformer
 */
class CategoryTransformer {
	/**
	 * Transforms SureCart categories to Omnisend categories
	 *
	 * @param array $categories
	 *
	 * @return array
	 */
	public function transform_categories( array $categories ): array {
		$data = array();

		foreach ( $categories as $category ) {
			$data[] = $this->transform_category( $category );
		}

		return $data;
	}

	/**
	 * Transform SureCart category to Omnisend category
	 *
	 * @param ProductCollection $category
	 *
	 * @return OmnisendCategory
	 */
	public function transform_category( ProductCollection $category ): OmnisendCategory {
		$omnisend_category = new OmnisendCategory();

		$omnisend_category->set_category_id( (string) $category->getAttribute( 'id' ) );
		$omnisend_category->set_title( (string) $category->getAttribute( 'name' ) );

		return $omnisend_category;
	}
}
