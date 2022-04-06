<?php
/**
 * Class for the handling notifications around plugins.
 *
 * @package wp-updates-notifier
 */

namespace Notifier;

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

		add_action( self::CRON_NAME, [ $cron, 'do_update_check' ] ); // action to link cron task to actual task.
	}

	/**
	 * Enable cron for this plugin. Check if a cron should be scheduled.
	 *
	 * @param bool|string $manual_interval For setting a manual cron interval.
	 *
	 * @return void
	 */
	public function enable( $manual_interval = false ) {
		$options          = $this->get_set_options( self::OPT_FIELD ); // Get settings.
		$current_schedule = wp_get_schedule( self::CRON_NAME ); // find if a schedule already exists.

		// if a manual cron interval is set, use this.
		if ( false !== $manual_interval ) {
			$options['frequency'] = $manual_interval;
		}

		if ( 'manual' === $options['frequency'] ) {
			do_action( 'sc_wpun_disable_cron' ); // Make sure no cron is setup as we are manual.
		} else {
			// check if the current schedule matches the one set in settings.
			if ( $current_schedule === $options['frequency'] ) {
				return;
			}

			// check the cron setting is valid.
			if ( ! in_array( $options['frequency'], $this->get_intervals(), true ) ) {
				return;
			}

			// Remove any cron's for this plugin first so we don't end up with multiple cron's doing the same thing.
			do_action( 'sc_wpun_disable_cron' );

			// Schedule cron for this plugin.
			wp_schedule_event( time(), $options['frequency'], self::CRON_NAME );
		}
	}

	/**
	 * Removes cron for this plugin.
	 *
	 * @return void
	 */
	public function disable() {
		wp_clear_scheduled_hook( self::CRON_NAME ); // clear cron.
	}

	/**
	 * This is run by the cron. The update check checks the core always, the
	 * plugins and themes if asked. If updates found email notification sent.
	 *
	 * @return void
	 */
	public function do_update_check() {
		$options = $this->get_set_options( self::OPT_FIELD ); // get settings.

		// Lets only do a check if one of the notification systems is set, if not, no one will get the message!
		if ( 1 === $options['email_notifications'] || 1 === $options['slack_notifications'] ) {
			$updates = []; // store all of the updates here.
			if ( 0 !== $options['notify_automatic'] ) { // should we notify about core updates?
				$updates['core'] = $this->core_update_check(); // check the WP core for updates.
			} else {
				$updates['core'] = false; // no core updates.
			}
			if ( 0 !== $options['notify_plugins'] ) { // are we to check for plugin updates?
				$updates['plugin'] = $this->plugins_update_check(); // check for plugin updates.
			} else {
				$updates['plugin'] = false; // no plugin updates.
			}
			if ( 0 !== $options['notify_themes'] ) { // are we to check for theme updates?
				$updates['theme'] = $this->themes_update_check(); // check for theme updates.
			} else {
				$updates['theme'] = false; // no theme updates.
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

			if ( ! empty( $updates['core'] ) || ! empty( $updates['plugin'] ) || ! empty( $updates['theme'] ) ) { // Did anything come back as need updating?

				// Send email notification.
				if ( 1 === $options['email_notifications'] ) {
					$message = $this->prepare_message( $updates, self::MARKUP_VARS_EMAIL );
					$this->send_email_message( $message );
				}

				// Send slack notification.
				if ( 1 === $options['slack_notifications'] ) {
					$message = $this->prepare_message( $updates, self::MARKUP_VARS_SLACK );
					$this->send_slack_message( $message );
				}
			}

			$this->log_last_check_time();
		}
	}

	/**
	 * Change the last time checked.
	 *
	 * @return void
	 */
	private function log_last_check_time() {
		$options                    = $this->get_set_options( self::OPT_FIELD );
		$options['last_check_time'] = time();
		$this->get_set_options( self::OPT_FIELD, $options );
	}

	/**
	 * Get cron intervals.
	 *
	 * @return Array cron intervals.
	 */
	private function get_intervals() {
		$intervals   = array_keys( $this->get_schedules() );
		$intervals[] = 'manual';
		return $intervals;
	}
}
