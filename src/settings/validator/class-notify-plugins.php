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
class Notify_Plugins implements Validator {

	/**
	 * Method to validate the input value for this setting.
	 *
	 * @param mixed $input The input value of the setting.
	 * @return mixed The input if valid, otherwise the existing setting.
	 */
	public function validate( $input ) {
		$sanitized_input = isset( $input ) ? absint( $input ) : 0;

		if ( $sanitized_input >= 0 && $sanitized_input <= 1 ) {
			return $sanitized_input;
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
			__( 'Invalid plugin updates value entered', 'wp-updates-notifier' ),
			'error'
		);

		return Settings::get_instance()->get( 'notify_plugins' );
	}
}
