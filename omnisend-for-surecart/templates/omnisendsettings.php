<?php
/**
 * Settings template for Omnisend
 *
 * @package OmnisendSureCartPlugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Omnisend\SureCartAddon\Provider\OmnisendSettingsProvider;
use Omnisend\SureCartAddon\Cron\OmnisendInitialSync;

// phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
?>

<div class="omnisend-sc-container">
	<div class="omnisend-sc-logo">
		<a href="https://app.omnisend.com" target="_blank">
			<img alt="Omnisend logo" src="<?php echo esc_url( plugins_url( '../assets/img/omnisend-logo.svg', __FILE__ ) ); ?>">
		</a>
	</div>

	<h2 class="omnisend-sc-settings-header">
		<?php echo esc_html__( 'Plugin settings', 'omnisend-for-surecart' ); ?>
	</h2>

	<h3 class="omnisend-sc-collect-header">
		<?php echo esc_html__( 'Collect subscribers at checkout', 'omnisend-for-surecart' ); ?>
	</h3>

	<form method="post" action="options.php">
		<?php settings_fields( 'omnisend_surecart_options_group' ); ?>
		<div class="omnisend-sc-option">
			<input 
				type="checkbox" 
				name="<?php echo esc_attr( OmnisendSettingsProvider::ALLOW_EMAIL_CONSENT_OPTION ); ?>" 
				value="1" <?php checked( '1', get_option( OmnisendSettingsProvider::ALLOW_EMAIL_CONSENT_OPTION ) ); ?>
				id="<?php echo esc_attr( OmnisendSettingsProvider::ALLOW_EMAIL_CONSENT_OPTION ); ?>"
			>
			<label for="<?php echo esc_attr( OmnisendSettingsProvider::ALLOW_EMAIL_CONSENT_OPTION ); ?>">
				<?php echo esc_html__( 'Add an email opt-in checkbox to the checkout page', 'omnisend-for-surecart' ); ?>
			</label>
			<label class="omnisend-sc-notice">
				<?php echo esc_html__( 'Customers who consent will be imported to your Omnisend account as email subscribers', 'omnisend-for-surecart' ); ?>
			</label>

			<div class="omnisend-sc-option block">
				<label for="<?php echo esc_attr( OmnisendSettingsProvider::EMAIL_TEXT_OPTION ); ?>">
					<?php echo esc_html__( 'Opt-in checkbox consent text', 'omnisend-for-surecart' ); ?>
				</label>
				<input 
					class="text"
					type="text"
					name="<?php echo esc_attr( OmnisendSettingsProvider::EMAIL_TEXT_OPTION ); ?>"
					value="<?php echo esc_attr( get_option( OmnisendSettingsProvider::EMAIL_TEXT_OPTION ) ); ?>"
				>
			</div>

			<div class="omnisend-sc-option pre-select">
				<input 
					type="checkbox" 
					name="<?php echo esc_attr( OmnisendSettingsProvider::ALLOW_EMAIL_PRE_SELECT_OPTION ); ?>" 
					value="1" <?php checked( '1', get_option( OmnisendSettingsProvider::ALLOW_EMAIL_PRE_SELECT_OPTION ) ); ?>
					id="<?php echo esc_attr( OmnisendSettingsProvider::ALLOW_EMAIL_PRE_SELECT_OPTION ); ?>"
				>
				<label for="<?php echo esc_attr( OmnisendSettingsProvider::ALLOW_EMAIL_PRE_SELECT_OPTION ); ?>">
					<?php echo esc_html__( 'Preselect opt-in checkbox in the checkout page', 'omnisend-for-surecart' ); ?>
				</label>
				<label class="omnisend-sc-notice">
					<?php echo esc_html__( 'Customers can deselect if they don\'t want email marketing ', 'omnisend-for-surecart' ); ?>
				</label>
			</div>
		</div>

		<div class="omnisend-sc-option">
			<input 
				type="checkbox"
				name="<?php echo esc_attr( OmnisendSettingsProvider::ALLOW_PHONE_CONSENT_OPTION ); ?>"
				value="1" <?php checked( '1', get_option( OmnisendSettingsProvider::ALLOW_PHONE_CONSENT_OPTION ) ); ?> 
				id="<?php echo esc_attr( OmnisendSettingsProvider::ALLOW_PHONE_CONSENT_OPTION ); ?>"
			>
			<label for="<?php echo esc_attr( OmnisendSettingsProvider::ALLOW_PHONE_CONSENT_OPTION ); ?>">
				<?php echo esc_html__( 'Add a phone no. opt-in checkbox to the checkout page', 'omnisend-for-surecart' ); ?>
			</label>
		</div>

		<div class="omnisend-sc-option block">
			<label for="<?php echo esc_attr( OmnisendSettingsProvider::PHONE_TEXT_OPTION ); ?>">
				<?php echo esc_html__( 'Opt-in checkbox consent text', 'omnisend-for-surecart' ); ?>
			</label>
			<input 
				class="text"
				type="text"
				name="<?php echo esc_attr( OmnisendSettingsProvider::PHONE_TEXT_OPTION ); ?>"
				value="<?php echo esc_attr( get_option( OmnisendSettingsProvider::PHONE_TEXT_OPTION ) ); ?>"
			>
		</div>
		<a href="#" class="omnisend-sc-learn-more" target="_blank">
			<?php echo esc_html__( 'Learn more opt-in settings', 'omnisend-for-surecart' ); ?>
		</a>

		<?php submit_button(); ?>
	</form>

	<table class="omnisend-sc-sync widefat fixed striped">
		<tr>
			<th><?php echo esc_html__( 'Sync Object', 'omnisend-for-surecart' ); ?></th>
			<th><?php echo esc_html__( 'Sync Status', 'omnisend-for-surecart' ); ?></th>
		</tr>
		<?php foreach ( OmnisendInitialSync::get_sync_status() as $sync_name => $sync_state ) : ?>
			<tr>
				<td><?php echo esc_html( $sync_name ); ?></td>
				<td><?php echo esc_html( $sync_state ); ?></td>
			</tr>
		<?php endforeach; ?>
	</table>

</div>
