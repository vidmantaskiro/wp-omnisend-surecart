<?php
/**
 * Omnisend Consent provider
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OmnisendConsentProvider
 */
class OmnisendConsentProvider {
	public const OMNISEND_EMAIL_CONSENT = 'omnisend_email_consent';
	public const OMNISEND_PHONE_CONSENT = 'omnisend_phone_consent';

	public function __construct() {
		add_filter( 'render_block', array( $this, 'add_checkbox' ), 10, 2 );
	}

	/**
	 * Adds Omnisend consent checkboxes when possible
	 *
	 * @param string $content
	 * @param array  $block
	 *
	 * @return string
	 */
	public function add_checkbox( string $content, array $block ): string {
		if ( $block['blockName'] !== 'surecart/submit' ) {
			return $content;
		}

		return $this->get_checkboxes() . $content;
	}

	/**
	 * Gets enabled Omnisend consent checkboxes
	 *
	 * @return string
	 */
	private function get_checkboxes(): string {
		$content     = '';
		$allow_email = (int) get_option( OmnisendSettingsProvider::ALLOW_EMAIL_CONSENT_OPTION );
		$allow_phone = (int) get_option( OmnisendSettingsProvider::ALLOW_PHONE_CONSENT_OPTION );

		if ( $allow_email !== 1 && $allow_phone !== 1 ) {
			return $content;
		}

		if ( $allow_email === 1 ) {
			$pre_selected = ( (int) get_option( OmnisendSettingsProvider::ALLOW_EMAIL_PRE_SELECT_OPTION ) === 1 ) ? 'checked' : '';
			$text         = get_option( OmnisendSettingsProvider::EMAIL_TEXT_OPTION );
			$content     .= $this->get_checkbox( self::OMNISEND_EMAIL_CONSENT, $text, $pre_selected );
		}

		if ( $allow_phone === 1 ) {
			$text     = get_option( OmnisendSettingsProvider::PHONE_TEXT_OPTION );
			$content .= $this->get_checkbox( self::OMNISEND_PHONE_CONSENT, $text );
		}

		return $content;
	}

	/**
	 * Returns Omnisend consent checkbox HTML
	 *
	 * @param string $code
	 * @param string $text
	 * @param string $pre_selected
	 *
	 * @return string
	 */
	private function get_checkbox( string $code, string $text, string $pre_selected = '' ): string {
		return '<sc-checkbox name="' . $code . '" value="consent" ' . $pre_selected . '>' . $text . '</sc-checkbox>'; // add dynamically phone field ?
	}
}
