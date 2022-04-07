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
class Slack_Notifications implements Validator {

	/**
	 * Method to validate the input value for this setting.
	 *
	 * @param mixed $input The input value of the setting.
	 * @return mixed The input if valid, otherwise the existing setting.
	 */
	public function validate( $input ) {

		$input = isset( $input ) ? absint( $input ) : 0;

		if ( $input > 1 ) {
			return $this->invalid();
		}

		return $input;
	}

	/**
	 * Set the settings error, and return the stored setting.
	 *
	 * @return mixed
	 */
	private function invalid(): int {
		add_settings_error( 'sc_wpun_settings_slack_notifications_slack_notifications', 'sc_wpun_settings_slack_notifications_slack_notifications_error', __( 'Invalid notification slack value entered', 'wp-updates-notifier' ), 'error' );
		return 0;
	}

}
