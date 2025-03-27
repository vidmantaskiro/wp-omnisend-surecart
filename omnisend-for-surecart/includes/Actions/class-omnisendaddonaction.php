<?php
/**
 * Omnisend Addon Action
 *
 * @package OmnisendSureCartPlugin
 */

declare(strict_types=1);

namespace Omnisend\SureCartAddon\Actions;

use Omnisend\SureCartAddon\Cron\OmnisendInitialSync;
use Omnisend\SureCartAddon\Service\OmnisendApiService;
use Omnisend\SureCartAddon\Service\OmnisendSnippetService;
use Omnisend\SureCartAddon\Provider\OmnisendSettingsProvider;
use Omnisend\SureCartAddon\Provider\OmnisendConsentProvider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * class OmnisendAddOnAction
 */
class OmnisendAddOnAction {
	/**
	 * Register actions
	 */
	public function __construct() {
		load_plugin_textdomain(
			'omnisend-for-surecart',
			false,
			plugin_basename( __DIR__ ) . '/../../languages/'
		);

		new OmnisendInitialSync( true );
		new OmnisendApiService();
		new OmnisendSnippetService();
		new OmnisendSettingsProvider();
		new OmnisendConsentProvider();
	}
}
