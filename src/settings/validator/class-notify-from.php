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
class Notify_From implements Validator {

	/**
	 * Method to validate the input value for this setting.
	 *
	 * @param mixed $input The input value of the setting.
	 * @param array $valid The current valid inputs array.
	 * @return mixed The input if valid, otherwise the stored setting.
	 */
	public function validate( $input, $valid = [] ) {
		/**
		 * If we don't have email notifications activated, then we simply
		 * ignore this field.
		 */
		if ( empty( $valid['email_notifications'] ) ) {
			return '';
		}

		/**
		 * If the input isn't set, we still consider it a valid
		 * value for this field.
		 */
		if ( empty( $input ) ) {
			return '';
		}

		$address = sanitize_email( trim( $input ) );

		if ( ! is_email( $address ) ) {
			return $this->invalid();
		}

		return $address;

	}

	/**
	 * Set the settings error, and return the stored setting.
	 *
	 * @return mixed
	 */
	private function invalid() {
		add_settings_error(
			'sc_wpun_settings_email_notifications_notify_from',
			'sc_wpun_settings_email_notifications_notify_from_error',
			__( 'Invalid email from entered', 'wp-updates-notifier' ),
			'error'
		);

		return Settings::get_instance()->get( 'notify_from' );
	}
}
