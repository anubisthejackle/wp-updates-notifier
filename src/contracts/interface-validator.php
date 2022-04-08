<?php
/**
 * The settings validator contract.
 *
 * @package wp-updates-notifier
 */

namespace Notifier\Contracts;

interface Validator {
	/**
	 * Method to validate the input value for this setting.
	 *
	 * @param mixed $input The input value of the setting.
	 * @param array $valid The current valid inputs array.
	 * @return mixed The input if valid, otherwise the stored setting.
	 */
	public function validate( $input, $valid = [] );
}
