<?php
/**
 * The Frequency setting validator.
 *
 * @package wp-updates-notifier
 */

namespace Notifier\Settings\Validator;

use Notifier\Contracts\Validator;

/**
 * Setting validator class.
 */
class Slack_Channel_Override implements Validator {

	/**
	 * Method to validate the input value for this setting.
	 *
	 * @param mixed $input The input value of the setting.
	 * @return mixed The input if valid, otherwise the existing setting.
	 */
	public function validate( $input ) {
		if ( '#' !== substr( $input, 0, 1 ) && '@' !== substr( $input, 0, 1 ) ) {
			return $this->invalid_channel();
		}

		if ( strpos( $input, ' ' ) ) {
			return $this->invalid_spaces();
		}

		return $input;
	}

	/**
	 * Set the settings error, and return the stored setting.
	 *
	 * @return mixed
	 */
	private function invalid_channel(): string {
		add_settings_error( 'sc_wpun_settings_slack_notifications_slack_channel_override', 'sc_wpun_settings_slack_notifications_slack_channel_override_error', __( 'Channel name must start with a # or @', 'wp-updates-notifier' ), 'error' );
		return '';
	}

	/**
	 * Set the settings error, and return the stored setting.
	 *
	 * @return mixed
	 */
	private function invalid_spaces(): string {
		add_settings_error( 'sc_wpun_settings_slack_notifications_slack_channel_override', 'sc_wpun_settings_slack_notifications_slack_channel_override_error', __( 'Channel name must not contain a space', 'wp-updates-notifier' ), 'error' );
		return '';
	}
}
