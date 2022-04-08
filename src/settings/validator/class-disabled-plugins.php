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
class Disabled_Plugins implements Validator {

	/**
	 * Method to validate the input value for this setting.
	 *
	 * @param mixed $input The input value of the setting.
	 * @param array $valid The current valid inputs array.
	 * @return mixed The input if valid, otherwise the stored setting.
	 */
	public function validate( $input, $valid = [] ) {
		if ( empty( $input ) ) {
			return [];
		}

		$active_plugins = array_flip( get_option( 'active_plugins' ) );
		$valid          = [];
		foreach ( $input as $new_disabled_plugin => $val ) {
			if ( isset( $active_plugins[ $new_disabled_plugin ] ) ) {
				$valid[ $new_disabled_plugin ] = 1;
			}
		}
	}
}
