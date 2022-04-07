<?php
/**
 * The Settings container class.
 *
 * @package wp-updates-notifier
 */

namespace Notifier;

/**
 * The singleton class for access the plugin settings.
 */
class Settings {

	/**
	 * The plugin settings container.
	 *
	 * @var array
	 */
	private $settings = null;

	/**
	 * The static instance of the class.
	 *
	 * @var \Notifier\Settings
	 */
	private static Settings $instance;

	const OPT_FIELD         = 'sc_wpun_settings';
	const OPT_VERSION_FIELD = 'sc_wpun_settings_ver';
	const OPT_VERSION       = '8.0';
	const DEFAULT_SETTINGS  = [
		'frequency'              => 'hourly',
		'email_notifications'    => 0,
		'notify_to'              => '',
		'notify_from'            => '',
		'slack_notifications'    => 0,
		'slack_webhook_url'      => '',
		'slack_channel_override' => '',
		'disabled_plugins'       => [],
		'notify_plugins'         => 1,
		'notify_themes'          => 1,
		'notify_automatic'       => 1,
		'hide_updates'           => 1,
		'notified'               => [
			'core'   => '',
			'plugin' => [],
			'theme'  => [],
		],
		'last_check_time'        => false,
	];

	const WRITEABLE = [
		'frequency',
		'notify_plugins',
		'notify_themes',
		'notify_automatic',
		'hide_updates',
		'notify_to',
		'notify_from',
		'email_notifications',
		'disabled_plugins',
		'slack_webhook_url',
		'slack_channel_override',
		'slack_notifications',
	];

	/**
	 * Locked constructor. Class should be accessed statically.
	 */
	private function __construct() {
		$this->settings = apply_filters( 'sc_wpun_get_options_filter', get_option( self::OPT_FIELD ), self::OPT_FIELD );
	}

	/**
	 * Return an instance of the Settings container class.
	 *
	 * @return \Notifier\Settings
	 */
	public static function get_instance(): \Notifier\Settings {
		if ( ! ( self::$instance instanceof self ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get the default settings.
	 *
	 * @return array The default settings.
	 */
	public function get_defaults(): array {
		return self::DEFAULT_SETTINGS;
	}

	/**
	 * The accessor method for getting individual settings from the plugin
	 * options.
	 *
	 * @param string $setting The setting name to access.
	 * @param mixed  $default The default value in case the option isn't found.
	 * @return mixed The setting value.
	 */
	public function get( string $setting, $default = null ) {
		return $this->settings[ $setting ] ?? $default;
	}

	/**
	 * The accessor method for setting individual settings from the plugin
	 * options.
	 *
	 * @param string $setting The setting name to set.
	 * @param mixed  $value The setting value to set.
	 * @return bool True if successful, false otherwise.
	 */
	public function set( string $setting, $value ): bool {
		$this->settings[ $setting ] = $value;
		return update_option( self::OPT_FIELD, apply_filters( 'sc_wpun_put_options_filter', $this->settings, self::OPT_FIELD ) );
	}

	/**
	 * Check if this plugin settings are up to date. Firstly check the version in
	 * the DB. If they don't match then load in defaults but don't override values
	 * already set. Also this will remove obsolete settings that are not needed.
	 *
	 * @return void
	 */
	public function maybe_migrate() {
		$current_ver = $this->get( self::OPT_VERSION_FIELD ); // Get current plugin version.
		if ( self::OPT_VERSION !== $current_ver ) { // is the version the same as this plugin?
			$options = (array) get_option( self::OPT_FIELD ); // get current settings from DB.

			// Get the default settings from the CONST.
			$defaults = self::DEFAULT_SETTINGS;

			// If we are upgrading from settings before settings version 7, turn on email notifications by default.
			if ( intval( $current_ver ) > 0 && intval( $current_ver ) < 7 ) {
				$defaults['email_notifications'] = 1;
			}

			// Intersect current options with defaults. Basically removing settings that are obsolete.
			$options = array_intersect_key( $options, $defaults );
			// Merge current settings with defaults. Basically adding any new settings with defaults that we dont have.
			$options = array_merge( $defaults, $options );
			$this->set( self::OPT_FIELD, $options ); // update settings.
			$this->set( self::OPT_VERSION_FIELD, self::OPT_VERSION ); // update settings version.
		}
	}
}
