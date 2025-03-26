<?php
/**
 * Omnisend Snippet service
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OmnisendSnippetService
 */
class OmnisendSnippetService {
	private const SNIPPET_META_CODE  = 'omnisend_action_required';
	private const IDENTIFY_VALUE_YES = 'yes';
	private const IDENTIFY_VALUE_NO  = 'no';

	/**
	 * Register actions
	 */
	public function __construct() {
		add_action( 'set_current_user', array( $this, 'change_snippet_status' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'track' ) );
	}

	/**
	 * Load Omnisend snippet to identify user, when needed
	 *
	 * @return void
	 */
	public function track(): void {
		$user_id = get_current_user_id();

		if ( $user_id === 0 ) {
			return;
		}

		if ( get_user_meta( $user_id, self::SNIPPET_META_CODE, true ) !== self::IDENTIFY_VALUE_YES ) {
			return;
		}

		$identifiers = array_filter(
			array(
				'email' => sanitize_email( wp_get_current_user()->user_email ),
				'phone' => '',
			)
		);

		$snippet_path = plugins_url( '../../assets/js/snippet.js', __FILE__ );

		wp_enqueue_script( 'omnisend-sc-snippet-script', $snippet_path, array(), OMNISEND_SURECART_ADDON_VERSION, true );
		wp_localize_script( 'omnisend-sc-snippet-script', 'omnisendIdentifiers', $identifiers );
		update_user_meta( $user_id, self::SNIPPET_META_CODE, self::IDENTIFY_VALUE_NO );
	}

	/**
	 * When SureCart swaps from guest to user, set flag to identify Omnisend contact with next page load
	 *
	 * @return void
	 */
	public function change_snippet_status(): void {
		$user_id = get_current_user_id();

		if ( $user_id === 0 ) {
			return;
		}

		update_user_meta( $user_id, self::SNIPPET_META_CODE, self::IDENTIFY_VALUE_YES );
	}
}
