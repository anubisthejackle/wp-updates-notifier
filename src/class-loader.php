<?php
/**
 * Main loader file for WP Updates Notifier.
 *
 * @package wp-updates-notifier
 */

namespace Notifier;

use Notifier\Admin\Settings;

/**
 * The Loader class is the plugin's kernel.
 */
class Loader {

	/**
	 * Initialize the WP Updates Notifier bootloader.
	 */
	public static function boot(): void {

		$loader = new self();

		/**
		 * Handle plugin activation and deactivation.
		 */
		register_activation_hook( __FILE__, [ $loader, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $loader, 'deactivate' ] );

		/**
		 * Handle internationalization.
		 */
		load_plugin_textdomain(
			'wp-updates-notifier',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'
		);

		/**
		 * Bootstrap the Admin settings page.
		 */
		Settings::boot();

		/**
		 * Bootstrap the update checkers.
		 */
		Core::boot();
		Plugins::boot();
		Themes::boot();

		/**
		 * Bootstrap the notifiers.
		 */
		Notifier::boot();

		/**
		 * Bootstrap the Cron Scheduler.
		 */
		Cron::boot();
	}

	/**
	 * Perform the on-activate hook.
	 */
	public function activate() {
		do_action( 'sc_wpun_enable_cron' );
	}

	/**
	 * Perform the on-deactivate hook.
	 */
	public function deactivate() {
		do_action( 'sc_wpun_disable_cron' );
	}

}
