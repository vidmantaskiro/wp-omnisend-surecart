<?php
/**
 * Omnisend Settings provider
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OmnisendSettingsProvider
 */
class OmnisendSettingsProvider {
	public const ALLOW_EMAIL_CONSENT_OPTION    = 'omnisend_sc_allow_email_consent';
	public const ALLOW_EMAIL_PRE_SELECT_OPTION = 'omnisend_sc_pre_select_email';
	public const ALLOW_PHONE_CONSENT_OPTION    = 'omnisend_sc_allow_phone_consent';
	public const PHONE_TEXT_OPTION             = 'omnisend_sc_phone_text';
	public const EMAIL_TEXT_OPTION             = 'omnisend_sc_email_text';
	public const STORE_CONNECTED_OPTION        = 'omnisend_sc_store_connected';

	public const OPTION_LIST = array(
		self::ALLOW_EMAIL_CONSENT_OPTION,
		self::ALLOW_EMAIL_PRE_SELECT_OPTION,
		self::ALLOW_PHONE_CONSENT_OPTION,
		self::PHONE_TEXT_OPTION,
		self::EMAIL_TEXT_OPTION,
	);

	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_omnisend-for-surecart-add-on/class-omnisend-surecartaddon.php', array( $this, 'add_settings_link' ) );

		wp_register_style(
			'omnisend-sc-settings',
			plugins_url( '../../assets/css/admin-settings.css', __FILE__ ),
			array(),
			OMNISEND_SURECART_ADDON_VERSION
		);
		wp_enqueue_style( 'omnisend-sc-settings' );
	}

	/**
	 * Adds Omnisend options page
	 *
	 * @return void
	 */
	public function add_menu(): void {
		add_options_page(
			'Omnisend for SureCart Options',
			'SureCart Omnisend',
			'manage_options',
			'omnisend-surecart',
			array( $this, 'get_options_content' )
		);
	}

	// phpcs:disable PluginCheck.CodeAnalysis.SettingSanitization.register_settingDynamic 
	/**
	 * Loads Omnisend admin page content
	 *
	 * @return void
	 */
	public function get_options_content(): void {
		require __DIR__ . '../../../templates/omnisendsettings.php';
	}

	/**
	 * Registers Omnisend options
	 *
	 * @return void
	 */
	public function register_settings(): void {
		$args = array(
			'default'           => null,
			'sanitize_callback' => 'sanitize_text_field',
		);

		foreach ( self::OPTION_LIST as $option_code ) {
			register_setting( 'omnisend_surecart_options_group', $option_code, $args );
		}
	}

	/**
	 * Adds settings button in plugin list
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	public function add_settings_link( array $links ): array {
		$settings_link = '<a href="options-general.php?page=omnisend-surecart">Settings</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Sets default Omnisend option values when they do not exist
	 *
	 * @return void
	 */
	public static function set_default_options(): void {
		if ( get_option( self::ALLOW_EMAIL_CONSENT_OPTION ) === false ) {
			update_option( self::ALLOW_EMAIL_CONSENT_OPTION, 1 );
		}

		if ( get_option( self::PHONE_TEXT_OPTION ) === false ) {
			update_option( self::PHONE_TEXT_OPTION, 'SMS me with news and offers' );
		}

		if ( get_option( self::EMAIL_TEXT_OPTION ) === false ) {
			update_option( self::EMAIL_TEXT_OPTION, 'Email me with news and offers' );
		}
	}
}
