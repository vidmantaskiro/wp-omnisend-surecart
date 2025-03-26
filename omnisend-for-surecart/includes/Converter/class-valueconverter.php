<?php
/**
 * Omnisend value converter
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Converter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ValueConverter
 */
class ValueConverter {
	/**
	 * Convert integer price
	 *
	 * @param int $price
	 *
	 * @return float
	 */
	public function convert_price( int $price ): float {
		return (float) $price / 100;
	}

	/**
	 * Convert timestamp to date
	 *
	 * @param int $timestamp
	 *
	 * @return string
	 */
	public function convert_timestamp( int $timestamp ): string {
		return gmdate( 'Y-m-d\Th:i:s\Z', $timestamp );
	}
}
