<?php
/**
 * The Frequency setting validator.
 *
 * @package wp-updates-notifier
 */

namespace Notifier\Settings\Validator;

use Notifier\Contracts\Validator;
use Notifier\Settings;

/**
 * Setting validator class.
 */
class Frequency implements Validator {

	/**
	 * Method to validate the input value for this setting.
	 *
	 * @param mixed $input The input value of the setting.
	 * @return mixed The input if valid, otherwise the existing setting.
	 */
	public function validate( $input ) {
		if ( in_array( $input, $this->get_intervals(), true ) ) {
			return $input;
		}

		return $this->invalid();
	}

	/**
	 * Set the settings error, and return the stored setting.
	 *
	 * @return mixed
	 */
	private function invalid() {
		add_settings_error(
			'sc_wpun_settings_main_frequency',
			'sc_wpun_settings_main_frequency_error',
			__( 'Invalid frequency entered', 'wp-updates-notifier' ),
			'error'
		);

		return Settings::get_instance()->get( 'frequency' );
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

	/**
	 * Get cron schedules.
	 *
	 * @return Array cron schedules.
	 */
	private function get_schedules() {
		$schedules = wp_get_schedules();
		uasort( $schedules, [ $this, 'sort_by_interval' ] );
		return $schedules;
	}

	/**
	 * Simple sort function.
	 *
	 * @param  int $a Integer for sorting.
	 * @param  int $b Integer for sorting.
	 *
	 * @return int Frequency internval.
	 */
	private function sort_by_interval( $a, $b ) {
		return $a['interval'] - $b['interval'];
	}
}
