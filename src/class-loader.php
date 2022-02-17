<?php
/**
 * Main loader file for WP Updates Notifier.
 *
 * @package Notifier
 */

namespace Notifier;

use Notifier\Admin\Settings;

class Loader {

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
    }

	public function activate() {
		do_action( 'sc_wpun_enable_cron' );
	}

	public function deactivate() {
		do_action( 'sc_wpun_disable_cron' );
	}

}
