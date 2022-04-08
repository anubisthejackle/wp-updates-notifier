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
class Slack_Webhook_Url implements Validator {

	/**
	 * Method to validate the input value for this setting.
	 *
	 * @param mixed $input The input value of the setting.
	 * @param array $valid The current valid inputs array.
	 * @return mixed The input if valid, otherwise the stored setting.
	 */
	public function validate( $input, $valid = [] ) {

		/**
		 * If we don't have Slack notifications activated,
		 * then we simply ignore this field.
		 */
		if ( empty( $valid['slack_notifications'] ) ) {
			return '';
		}

		if ( false === \filter_var( $input, FILTER_VALIDATE_URL ) ) {
			return $this->invalid();
		}

		return $input;
	}

	/**
	 * Set the settings error, and return the stored setting.
	 *
	 * @return mixed
	 */
	private function invalid(): string {
		add_settings_error(
			'sc_wpun_settings_slack_notifications_slack_webhook_url',
			'sc_wpun_settings_slack_notifications_slack_webhook_url_error',
			__( 'Invalid webhook url entered', 'wp-updates-notifier' ),
			'error'
		);

		return '';
	}
}
