<?php
/**
 * Omnisend contact transformer
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Transformers;

use Omnisend\SDK\V1\Contact as OmnisendContact;
use Omnisend\SureCartAddon\Provider\OmnisendConsentProvider;
use SureCart\Models\Customer;
use SureCart\Models\Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ContactTransformer
 */
class ContactTransformer {
	public const ADD_CONTACT_ID          = true;
	public const OMNISEND_CONTACT_COOKIE = 'omnisendContactID';

	private const CONSENT_VALUE = 'source: surecart';

	/**
	 * Transforms SureCart contacts to Omnisend Contacts
	 *
	 * @param array $customers
	 *
	 * @return array
	 */
	public function transform_contacts( array $customers ): array {
		$data = array();

		foreach ( $customers as $customer ) {
			$data[] = $this->transform_contact( $customer );
		}

		return $data;
	}

	/**
	 * Transforms SureCart contact to Omnisend contact
	 *
	 * @param Customer $customer
	 *
	 * @return OmnisendContact
	 */
	public function transform_contact( Customer $customer ): OmnisendContact {
		$omnisend_contact = new OmnisendContact();
		$address          = $customer->getAttribute( 'shipping_address' );

		$omnisend_contact->set_email( $customer->getAttribute( 'email' ) );
		$omnisend_contact->set_first_name( $customer->getAttribute( 'first_name' ) );
		$omnisend_contact->set_last_name( $customer->getAttribute( 'last_name' ) );
		$omnisend_contact->add_tag( self::CONSENT_VALUE );

		return $this->add_address( $omnisend_contact, $address );
	}

	/**
	 * Transforms contact for use in Omnisend event
	 *
	 * @param string $email
	 * @param bool   $try_cookie
	 *
	 * @return Omnisendcontact
	 */
	public function transform_contact_for_event( string $email, bool $try_cookie = false ): OmnisendContact {
		$omnisend_contact = new OmnisendContact();
		$omnisend_contact->add_tag( self::CONSENT_VALUE );

		if ( $try_cookie && isset( $_COOKIE[ self::OMNISEND_CONTACT_COOKIE ] ) ) {
			$omnisend_contact->set_id( sanitize_text_field( wp_unslash( $_COOKIE[ self::OMNISEND_CONTACT_COOKIE ] ) ) );

			return $omnisend_contact;
		}

		$omnisend_contact->set_email( $email );

		return $omnisend_contact;
	}

	/**
	 * Transforms SureCart contact to Omnisend contact from Order information with consents
	 *
	 * @param Order $order
	 *
	 * @return OmnisendContact
	 */
	public function transform_contact_by_order( Order $order ): OmnisendContact {
		$omnisend_contact = new OmnisendContact();
		$checkout_data    = $order->getAttribute( 'checkout' );
		$address          = $checkout_data->getAttribute( 'shipping_address' );
		$meta_data        = $checkout_data->getAttribute( 'metadata' );

		$omnisend_contact->set_email( $checkout_data->getAttribute( 'inherited_email' ) );
		$omnisend_contact->set_first_name( $checkout_data->getAttribute( 'first_name' ) );
		$omnisend_contact->set_last_name( $checkout_data->getAttribute( 'last_name' ) );
		$omnisend_contact->set_phone( $checkout_data->getAttribute( 'phone' ) );
		$omnisend_contact->add_tag( self::CONSENT_VALUE );

		$omnisend_contact = $this->add_consent( $omnisend_contact, $meta_data );

		return $this->add_address( $omnisend_contact, $address );
	}

	/**
	 * Adds address information from SureCart address to Omnisend contact
	 *
	 * @param OmnisendContact $omnisend_contact
	 * @param Object          $address
	 *
	 * @return OmnisendContact
	 */
	private function add_address( OmnisendContact $omnisend_contact, object $address ): OmnisendContact {
		if ( empty( $address ) ) {
			return $omnisend_contact;
		}

		$omnisend_contact->set_address( property_exists( $address, 'line_1' ) ? $address->line_1 : null );
		$omnisend_contact->set_city( property_exists( $address, 'city' ) ? $address->city : null );
		$omnisend_contact->set_country( property_exists( $address, 'country' ) ? $address->country : null );
		$omnisend_contact->set_postal_code( property_exists( $address, 'postal_code' ) ? $address->postal_code : null );
		$omnisend_contact->set_state( property_exists( $address, 'state' ) ? $address->state : null );

		return $omnisend_contact;
	}

	/**
	 * Adds consents from SureCart order metadata
	 *
	 * @param OmnisendContact $omnisend_contact
	 * @param Object          $metadata
	 *
	 * @return OmnisendContact
	 */
	private function add_consent( OmnisendContact $omnisend_contact, object $metadata ): OmnisendContact {
		if ( property_exists( $metadata, OmnisendConsentProvider::OMNISEND_EMAIL_CONSENT ) ) {
			$omnisend_contact->set_email_subscriber();
			$omnisend_contact->set_email_consent( self::CONSENT_VALUE );
		} else {
			$omnisend_contact->set_email_consent( self::CONSENT_VALUE );
		}

		if ( property_exists( $metadata, OmnisendConsentProvider::OMNISEND_PHONE_CONSENT ) ) {
			$omnisend_contact->set_phone_subscriber();
			$omnisend_contact->set_phone_consent( self::CONSENT_VALUE );
		} else {
			$omnisend_contact->set_phone_consent( self::CONSENT_VALUE );
		}

		return $omnisend_contact;
	}
}
