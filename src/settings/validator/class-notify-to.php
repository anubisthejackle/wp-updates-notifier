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
class Notify_To implements Validator {

	/**
	 * Method to validate the input value for this setting.
	 *
	 * @param mixed $input The input value of the setting.
	 * @return mixed The input if valid, otherwise the existing setting.
	 */
	public function validate( $input ) {
		/**
		 * If the input isn't set, we still consider it a valid
		 * value for this field.
		 */
		if ( empty( $input ) ) {
			return '';
		}

		$emails = explode( ',', $input );

		$sanitized_emails = [];
		foreach ( $emails as $email ) {
			$address = sanitize_email( trim( $email ) );

			if ( ! is_email( $address ) ) {
				return $this->invalid();
			}

			$sanitized_emails[] = $address;
		}

		return $sanitized_emails;
	}

	/**
	 * Set the settings error, and return the stored setting.
	 *
	 * @return mixed
	 */
	private function invalid() {
		add_settings_error(
			'sc_wpun_settings_email_notifications_notify_to',
			'sc_wpun_settings_email_notifications_notify_to',
			__( 'One or more email to addresses are invalid', 'wp-updates-notifier' ),
			'error'
		);

		return Settings::get_instance()->get( 'notify_to' );
	}
}
