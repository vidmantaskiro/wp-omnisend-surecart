<?php
/**
 * Omnisend Discount transformer
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Transformers\Events\Components;

use SureCart\Models\Discount;
use Omnisend\SDK\V1\Events\Components\Discount as OmnisendDiscount;
use Omnisend\SureCartAddon\Converter\ValueConverter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DiscountTransformer
 */
class DiscountTransformer {
	/**
	 * @var ValueConverter
	 */
	private $value_converter;

	public function __construct() {
		$this->value_converter = new ValueConverter();
	}

	/**
	 * Transforms SureCart discount to Omnisend discount
	 *
	 * @param ?Discount $discount
	 *
	 * @return ?OmnisendDiscount
	 */
	public function transform_discount( ?Discount $discount ): ?OmnisendDiscount {
		if ( ! $discount ) {
			return null;
		}

		$amount = abs( $this->value_converter->convert_price( (int) $discount->getAttribute( 'discount_amount' ) ) );
		$code   = $discount->getAttribute( 'promotion' )->code;

		$omnisend_discount = new OmnisendDiscount();

		$omnisend_discount->set_amount( $amount );
		$omnisend_discount->set_code( $code );
		$omnisend_discount->set_type( '-' );

		return $omnisend_discount;
	}
}
