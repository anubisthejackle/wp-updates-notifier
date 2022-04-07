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
class Email_Notifications implements Validator {

	/**
	 * Method to validate the input value for this setting.
	 *
	 * @param mixed $input The input value of the setting.
	 * @return mixed The input if valid, otherwise the existing setting.
	 */
	public function validate( $input ) {
		$input = ! empty( $input ) ? absint( $input ) : 0;

		if ( 1 < $input ) {
			return $this->invalid();
		}

		return $input;
	}

	/**
	 * Set the settings error, and return the stored setting.
	 *
	 * @return mixed
	 */
	private function invalid() {
		add_settings_error(
			'sc_wpun_settings_email_notifications_email_notifications',
			'sc_wpun_settings_email_notifications_email_notifications_error',
			__( 'Invalid notification email value entered', 'wp-updates-notifier' ),
			'error'
		);

		return Settings::get_instance()->get( 'email_notifications' );
	}
}
