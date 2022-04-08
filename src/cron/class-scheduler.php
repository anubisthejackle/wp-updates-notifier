<?php
/**
 * The Cron scheduler.
 *
 * @package wp-updates-notifier
 */

namespace Notifier\Cron;

/**
 * Manages and gates access to the cron scheduler for the automation
 * tasks.
 */
class Scheduler {

	/**
	 * An instance of Scheduler.
	 *
	 * @var Notifier\Cron\Scheduler
	 */
	private static $instance;

	/**
	 * Restrict constructor to only being loadable via the get_instance method.
	 */
	private function __construct(){}

	/**
	 * Get the current instance of this class.
	 */
	public static function get_instance() {
		if ( ! ( self::$instance instanceof self ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get cron intervals.
	 *
	 * @return Array cron intervals.
	 */
	public function get_intervals() {
		$intervals   = array_keys( $this->get_schedules() );
		$intervals[] = 'manual';
		return $intervals;
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

	/**
	 * Get cron schedules.
	 *
	 * @return Array cron schedules.
	 */
	public function get_schedules() {
		$schedules = wp_get_schedules();
		uasort( $schedules, [ $this, 'sort_by_interval' ] );
		return $schedules;
	}
}
