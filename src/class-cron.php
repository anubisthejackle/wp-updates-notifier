<?php
/**
 * Class for the handling notifications around plugins.
 *
 * @package wp-updates-notifier
 */

namespace Notifier;

use Notifier\Cron\Scheduler;

/**
 * The Cron class manages the scheduled tasks.
 */
class Cron {
	const CRON_NAME = 'sc_wpun_update_check';

	/**
	 * Initialize the Cron Scheduler.
	 */
	public static function boot(): void {
		$cron = new self();
		add_action( 'sc_wpun_enable_cron', [ $cron, 'enable' ] );
		add_action( 'sc_wpun_disable_cron', [ $cron, 'disable' ] );

		add_action( self::CRON_NAME, [ $cron, 'do_update_check' ] );
	}

	/**
	 * Enable cron for this plugin. Check if a cron should be scheduled.
	 *
	 * @param bool|string $manual_interval For setting a manual cron interval.
	 *
	 * @return void
	 */
	public function enable( $manual_interval = false ) {
		// if a manual cron interval is set, use this.
		if ( false !== $manual_interval ) {
			do_action( 'sc_wpun_disable_cron' ); // Make sure no cron is setup as we are manual.
			return;
		}

		$settings         = Settings::get_instance();
		$current_schedule = wp_get_schedule( self::CRON_NAME ); // find if a schedule already exists.

		// check if the current schedule matches the one set in settings.
		if ( $current_schedule === $settings->get( 'frequency' ) ) {
			return;
		}

		// check the cron setting is valid.
		if ( ! in_array( $settings->get( 'frequency' ), Scheduler::get_instance()->get_intervals(), true ) ) {
			return;
		}

		// Remove any cron's for this plugin first so we don't end up with multiple cron's doing the same thing.
		do_action( 'sc_wpun_disable_cron' );

		// Schedule cron for this plugin.
		wp_schedule_event( \time(), $settings->get( 'frequency' ), self::CRON_NAME );
	}

	/**
	 * Removes cron for this plugin.
	 *
	 * @return void
	 */
	public function disable() {
		wp_clear_scheduled_hook( self::CRON_NAME );
	}

	/**
	 * This is run by the cron. The update check checks the core always, the
	 * plugins and themes if asked. If updates found email notification sent.
	 *
	 * @return void
	 */
	public function do_update_check() {
		$settings = Settings::get_instance();

		// Lets only do a check if one of the notification systems is set, if not, no one will get the message!
		if ( ! ( 1 === $settings->get( 'email_notifications' ) || 1 === $settings->get( 'slack_notifications' ) ) ) {
			return;
		}

		$updates = [
			'core'   => false,
			'plugin' => false,
			'theme'  => false,
		];

		$updaters = [
			'core'   => fn() => new Core(),
			'plugin' => fn() => new Plugins(),
			'theme'  => fn() => new Themes(),
		];

		foreach ( $updaters as $type => $updater ) {
			$updates[ $type ] = $updater()->update_check();
		}

		/**
		 * Filters the updates before they're parsed for sending.
		 *
		 * Change the updates array of core, plugins, and themes to be notified about.
		 *
		 * @since 1.6.1
		 *
		 * @param array  $updates Array of updates to notify about.
		 */
		$updates = apply_filters( 'sc_wpun_updates', $updates );
		$this->log_last_check_time();

		$has_updates = array_reduce( $updates, fn( $carry, $updated ) => $carry || false !== $updated, false );

		/**
		 * If we have no updates there is no need to send a notification.
		 */
		if ( false === $has_updates ) {
			return;
		}

		do_action( 'notifier_send_notification', $updates );
	}

	/**
	 * Change the last time checked.
	 *
	 * @return void
	 */
	private function log_last_check_time() {
		Settings::get_instance()->set( 'last_check_time', \time() );
	}
}
