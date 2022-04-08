<?php
/**
 * Class for the WP Updates Notifier settings page.
 *
 * @package wp-updates-notifier
 */

namespace Notifier\Admin;

use Notifier\Notifier\Email;
use Notifier\Settings as SettingsContainer;
use Notifier\Settings\Validator\Disabled_Plugins;
use Notifier\Settings\Validator\Email_Notifications;
use Notifier\Settings\Validator\Frequency;
use Notifier\Settings\Validator\Hide_Updates;
use Notifier\Settings\Validator\Notify_Automatic;
use Notifier\Settings\Validator\Notify_From;
use Notifier\Settings\Validator\Notify_Plugins;
use Notifier\Settings\Validator\Notify_Themes;
use Notifier\Settings\Validator\Notify_To;
use Notifier\Settings\Validator\Slack_Channel_Override;
use Notifier\Settings\Validator\Slack_Notifications;
use Notifier\Settings\Validator\Slack_Webhook_Url;

/**
 * The Settings class manages creating the settings page, menu items,
 * and maintaining the actual settings submissions.
 */
class Settings {
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

	/**
	 * Initialize the Admin Settings options.
	 */
	public static function boot(): void {
		$settings = new self();
		add_action( 'init', [ SettingsContainer::get_instance(), 'maybe_migrate' ] );
		add_filter( 'plugin_action_links', [ $settings, 'plugin_action_links' ], 10, 2 );

		add_action( 'admin_menu', [ $settings, 'admin_settings_menu' ] );
		add_action( 'admin_init', [ $settings, 'admin_settings_init' ] );
		add_action( 'admin_init', [ $settings, 'remove_update_nag_for_nonadmins' ] );
		add_action( 'manage_plugins_custom_column', [ $settings, 'manage_plugins_custom_column' ], 10, 3 );
		add_action( 'manage_plugins_columns', [ $settings, 'manage_plugins_columns' ] );
		add_action( 'admin_head', [ $settings, 'custom_admin_css' ] );
		add_action( 'admin_footer', [ $settings, 'custom_admin_js' ] );
		add_action( 'wp_ajax_toggle_plugin_notification', [ $settings, 'toggle_plugin_notification' ] );
	}

	/**
	 * Adds the settings link under the plugin on the plugin screen.
	 *
	 * @param array  $links Links to list on the plugin screen.
	 * @param string $file Filename.
	 *
	 * @return array $links
	 */
	public function plugin_action_links( $links, $file ) {
		if ( plugin_basename( __FILE__ ) === $file ) {
			$settings_link = '<a href="' . admin_url( 'options-general.php?page=wp-updates-notifier' ) . '">' . __( 'Settings', 'wp-updates-notifier' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}
	/**
	 * Add admin menu.
	 *
	 * @return void
	 */
	public function admin_settings_menu() {
		add_options_page( __( 'Updates Notifier', 'wp-updates-notifier' ), __( 'Updates Notifier', 'wp-updates-notifier' ), 'manage_options', 'wp-updates-notifier', [ $this, 'settings_page' ] );
	}

	/**
	 * Add all of the settings for the settings page.
	 *
	 * @return void
	 */
	public function admin_settings_init() {
		register_setting( self::OPT_FIELD, self::OPT_FIELD, [ $this, 'sc_wpun_settings_validate' ] ); // Register Settings.

		add_settings_section( 'sc_wpun_settings_main', __( 'Settings', 'wp-updates-notifier' ), [ $this, 'sc_wpun_settings_main_text' ], 'wp-updates-notifier' ); // Make settings main section.
		add_settings_field( 'sc_wpun_settings_main_frequency', __( 'Frequency to check', 'wp-updates-notifier' ), [ $this, 'sc_wpun_settings_main_field_frequency' ], 'wp-updates-notifier', 'sc_wpun_settings_main' );
		add_settings_field( 'sc_wpun_settings_main_notify_plugins', __( 'Notify about plugin updates?', 'wp-updates-notifier' ), [ $this, 'sc_wpun_settings_main_field_notify_plugins' ], 'wp-updates-notifier', 'sc_wpun_settings_main' );
		add_settings_field( 'sc_wpun_settings_main_notify_themes', __( 'Notify about theme updates?', 'wp-updates-notifier' ), [ $this, 'sc_wpun_settings_main_field_notify_themes' ], 'wp-updates-notifier', 'sc_wpun_settings_main' );
		add_settings_field( 'sc_wpun_settings_main_notify_automatic', __( 'Notify about core updates?', 'wp-updates-notifier' ), [ $this, 'sc_wpun_settings_main_field_notify_automatic' ], 'wp-updates-notifier', 'sc_wpun_settings_main' );
		add_settings_field( 'sc_wpun_settings_main_hide_updates', __( 'Hide core WP update nag from non-admin users?', 'wp-updates-notifier' ), [ $this, 'sc_wpun_settings_main_field_hide_updates' ], 'wp-updates-notifier', 'sc_wpun_settings_main' );

		// Email notification settings.
		add_settings_section( 'sc_wpun_settings_email_notifications', __( 'Email Notifications', 'wp-updates-notifier' ), [ $this, 'sc_wpun_settings_email_notifications_text' ], 'wp-updates-notifier' );
		add_settings_field( 'sc_wpun_settings_email_notifications_email_notifications', __( 'Send email notifications?', 'wp-updates-notifier' ), [ $this, 'sc_wpun_settings_email_notifications_field_email_notifications' ], 'wp-updates-notifier', 'sc_wpun_settings_email_notifications' );
		add_settings_field( 'sc_wpun_settings_email_notifications_notify_to', __( 'Notify email to', 'wp-updates-notifier' ), [ $this, 'sc_wpun_settings_email_notifications_field_notify_to' ], 'wp-updates-notifier', 'sc_wpun_settings_email_notifications' );
		add_settings_field( 'sc_wpun_settings_email_notifications_notify_from', __( 'Notify email from', 'wp-updates-notifier' ), [ $this, 'sc_wpun_settings_email_notifications_field_notify_from' ], 'wp-updates-notifier', 'sc_wpun_settings_email_notifications' );

		// Slack notification settings.
		add_settings_section( 'sc_wpun_settings_slack_notifications', __( 'Slack Notifications', 'wp-updates-notifier' ), [ $this, 'sc_wpun_settings_slack_notifications_text' ], 'wp-updates-notifier' );
		add_settings_field( 'sc_wpun_settings_slack_notifications_slack_notifications', __( 'Send slack notifications?', 'wp-updates-notifier' ), [ $this, 'sc_wpun_settings_slack_notifications_field_slack_notifications' ], 'wp-updates-notifier', 'sc_wpun_settings_slack_notifications' );
		add_settings_field( 'sc_wpun_settings_slack_notifications_slack_webhook_url', __( 'Webhook url', 'wp-updates-notifier' ), [ $this, 'sc_wpun_settings_slack_notifications_field_slack_webhook_url' ], 'wp-updates-notifier', 'sc_wpun_settings_slack_notifications' );
		add_settings_field( 'sc_wpun_settings_slack_notifications_slack_channel_override', __( 'Channel to notify', 'wp-updates-notifier' ), [ $this, 'sc_wpun_settings_slack_notifications_field_slack_channel_override' ], 'wp-updates-notifier', 'sc_wpun_settings_slack_notifications' );
	}

	/**
	 * Removes the update nag for non admin users.
	 *
	 * @return void
	 */
	public function remove_update_nag_for_nonadmins() {
		if ( 1 === SettingsContainer::get_instance()->get( 'hide_updates' ) ) { // is this enabled?
			if ( ! current_user_can( 'update_plugins' ) ) { // can the current user update plugins?
				remove_action( 'admin_notices', 'update_nag', 3 ); // no they cannot so remove the nag for them.
			}
		}
	}

	/**
	 * Alter the columns on the plugins page to show enable and disable notifications.
	 *
	 * @param string $column_name Name of the column.
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array  $plugin_data An array of plugin data.
	 */
	public function manage_plugins_custom_column( $column_name, $plugin_file, $plugin_data ) {
		if ( 1 === SettingsContainer::get_instance()->get( 'notify_plugins' ) ) {
			if ( 'update_notifications' === $column_name ) {
				if ( is_plugin_active( $plugin_file ) ) {
					if ( isset( SettingsContainer::get_instance()->get( 'disabled_plugins' )[ $plugin_file ] ) ) {
						echo '<button class="sc_wpun_btn sc_wpun_btn_disable" data-toggle="enable" data-file="' . esc_attr( $plugin_file ) . '">' . esc_html_e( 'Notifications Disabled', 'wp-updates-notifier' ) . '</button>';
					} else {
						echo '<button class="sc_wpun_btn sc_wpun_btn_enable" data-toggle="disable" data-file="' . esc_attr( $plugin_file ) . '">' . esc_html_e( 'Notifications Enabled', 'wp-updates-notifier' ) . '</button>';
					}
				}
			}
		}
	}

	/**
	 * Alter the columns on the plugins page to show enable and disable notifications.
	 *
	 * @param array $column_headers An array of column headers.
	 */
	public function manage_plugins_columns( $column_headers ) {
		if ( 1 === SettingsContainer::get_instance()->get( 'notify_plugins' ) ) {
			$column_headers['update_notifications'] = __( 'Update Notifications', 'wp-updates-notifier' );
		}
		return $column_headers;
	}

	/**
	 * Custom css for the plugins.php page.
	 *
	 * @return void
	 */
	public function custom_admin_css() {
		if ( 1 === SettingsContainer::get_instance()->get( 'notify_plugins' ) ) {
			echo '<style type="text/css">

			.column-update_notifications{
				width: 15%;
			}

			.sc_wpun_btn:before {
				font-family: "dashicons";
				display: inline-block;
				-webkit-font-smoothing: antialiased;
				font: normal 20px/1;
				vertical-align: top;
				margin-right: 5px;
				margin-right: 0.5rem;
			}

			.sc_wpun_btn_enable:before {
				content: "\f12a";
				color: green;
			}

			.sc_wpun_btn_disable:before {
				content: "\f153";
				color: red;
			}

			.sc_wpun_btn_enable:hover:before {
				content: "\f153";
				color: red;
			}

			.sc_wpun_btn_disable:hover:before {
				content: "\f12a";
				color: green;
			}

			</style>';
		}
	}

	/**
	 * Custom js for the plugins.php page.
	 *
	 * @return void
	 */
	public function custom_admin_js() {
		global $pagenow;
		if ( 'plugins.php' === $pagenow ) :
			?>
			<script type="text/javascript" >
			jQuery(document).ready(function($) {
				$( '.sc_wpun_btn' ).click(function(e) {
					e.preventDefault();

					var data = {
						'action': 'toggle_plugin_notification',
						'toggle': $(e.target).data().toggle,
						'plugin_file': $(e.target).data().file,
						'_ajax_nonce': "<?php echo esc_attr( wp_create_nonce( 'toggle_plugin_notification' ) ); ?>",
					};

					jQuery.post(ajaxurl, data, function(response) {
						if ( 'success' == response ) {
							if ( 'disable' == $(e.target).data().toggle ) {
								$(e.target).data( 'toggle', 'enable' );
								$(e.target).removeClass( 'sc_wpun_btn_enable' );
								$(e.target).addClass( 'sc_wpun_btn_disable' );
								$(e.target).text( '<?php echo esc_html( __( 'Notifications Disabled', 'wp-updates-notifier' ) ); ?>' );
							} else {
								$(e.target).data( 'toggle', 'disable' );
								$(e.target).removeClass( 'sc_wpun_btn_disable' );
								$(e.target).addClass( 'sc_wpun_btn_enable' );
								$(e.target).text( '<?php echo esc_html( __( 'Notifications Enabled', 'wp-updates-notifier' ) ); ?>' );
							}
						}
					});
				});
			});
			</script>
			<?php
		endif;
	}

	/**
	 * Function to flip the notifications off / on for a plugin.
	 *
	 * @return void
	 */
	public function toggle_plugin_notification() {
		check_ajax_referer( 'toggle_plugin_notification' );
		if ( isset( $_POST['plugin_file'] ) && isset( $_POST['toggle'] ) && current_user_can( 'manage_options' ) ) {
			$plugin_file      = sanitize_text_field( wp_unslash( $_POST['plugin_file'] ) );
			$toggle           = sanitize_text_field( wp_unslash( $_POST['toggle'] ) );
			$settings         = SettingsContainer::get_instance();
			$disabled_plugins = $settings->get( 'disabled_plugins', [] );
			if ( 'disable' === $toggle ) {
				$disabled_plugins[ $plugin_file ] = 1;
				echo 'success';
			} elseif ( 'enable' === $toggle ) {
				unset( $disabled_plugins[ $plugin_file ] );
				echo 'success';
			} else {
				echo 'failure';
				wp_die();
				exit;
			}
			$settings->set( 'disabled_plugins', $disabled_plugins );
		}
		wp_die();
		exit;
	}

	/**
	 * Output settings page and trigger sending tests.
	 *
	 * @return void
	 */
	public function settings_page() {
		// Trigger tests if they are ready to be sent.
		$sc_wpun_send_test_slack = get_transient( 'sc_wpun_send_test_slack' );
		if ( $sc_wpun_send_test_slack ) {
			delete_transient( 'sc_wpun_send_test_slack' );
			$this->send_test_slack( self::MARKUP_VARS_SLACK );
		}
		$sc_wpun_send_test_email = get_transient( 'sc_wpun_send_test_email' );
		if ( $sc_wpun_send_test_email ) {
			delete_transient( 'sc_wpun_send_test_email' );
			$this->send_test_email( self::MARKUP_VARS_EMAIL );
		}

		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );
		$settings    = SettingsContainer::get_instance();
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Updates Notifier', 'wp-updates-notifier' ); ?></h2>

			<p>
				<span class="description">
				<?php
				if ( empty( $settings->get( 'last_check_time' ) ) ) {
					$scan_date = __( 'Never', 'wp-updates-notifier' );
				} else {
					$scan_date = sprintf(
						// translators: The formatted date and time of the last check time.
						__( '%1$1s @ %2$2s', 'wp-updates-notifier' ),
						gmdate( $date_format, $settings->get( 'last_check_time' ) ),
						gmdate( $time_format, $settings->get( 'last_check_time' ) )
					);
				}

				echo esc_html( __( 'Last scanned: ', 'wp-updates-notifier' ) . $scan_date );
				?>
				</span>
			</p>

			<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
				<?php
				settings_fields( 'sc_wpun_settings' );
				do_settings_sections( 'wp-updates-notifier' );
				?>
				<p>&nbsp;</p>
				<input class="button-primary" name="Submit" type="submit" value="<?php esc_attr_e( 'Save settings', 'wp-updates-notifier' ); ?>" />
				<input class="button" name="submitwithemail" type="submit" value="<?php esc_attr_e( 'Save settings with test email', 'wp-updates-notifier' ); ?>" />
				<input class="button" name="submitwithslack" type="submit" value="<?php esc_attr_e( 'Save settings with test slack post', 'wp-updates-notifier' ); ?>" />
				<br><br>
				<input class="button" name="restoredefaults" type="submit" value="<?php esc_attr_e( 'Restore Default Settings', 'wp-updates-notifier' ); ?>" />
			</form>
		</div>
		<?php
	}

	/**
	 * Validate and sanitize all of the settings from the page form.
	 *
	 * @param array $input Array of unsanitized options from the page form.
	 *
	 * @return array Array of sanitized and validated settings.
	 */
	public function sc_wpun_settings_validate( $input ) {
		if ( isset( $_POST['restoredefaults'] ) ) {
			// override all settings.
			return SettingsContainer::get_instance()->get_defaults();
		}

		// disabled plugins will only be set through the plugins page, so we only check the admin referer for the options page if they aren't set.
		if ( ! isset( $input['disabled_plugins'] ) ) {
			check_admin_referer( 'sc_wpun_settings-options' );
		}


		$validators = [
			'disabled_plugins'       => fn() => new Disabled_Plugins(),
			'frequency'              => fn() => new Frequency(),
			'notify_plugins'         => fn() => new Notify_Plugins(),
			'notify_themes'          => fn() => new Notify_Themes(),
			'notify_automatic'       => fn() => new Notify_Automatic(),
			'hide_updates'           => fn() => new Hide_Updates(),
			'email_notifications'    => fn() => new Email_Notifications(),
			'notify_to'              => fn() => new Notify_To(),
			'notify_from'            => fn() => new Notify_From(),
			'slack_notifications'    => fn() => new Slack_Notifications(),
			'slack_webhook_url'      => fn() => new Slack_Webhook_Url(),
			'slack_channel_override' => fn() => new Slack_Channel_Override(),
		];

		$valid = [];
		foreach( $validators as $name => $validator ){
			if( ! isset( $input[ $name ] ) ){
				continue;
			}

			$valid[ $name ] = $validator()->validate( $input[ $name ], $valid );
		}

		// Parse sending test notifiations.
		if ( isset( $_POST['submitwithemail'] ) ) {
			if ( '' !== $valid['notify_to'] && '' !== $valid['notify_from'] ) {
				set_transient( 'sc_wpun_send_test_email', 1 );
			} else {
				add_settings_error( 'sc_wpun_settings_email_notifications_email_notifications', 'sc_wpun_settings_email_notifications_email_notifications_error', __( 'Can not send test email. Email settings are invalid.', 'wp-updates-notifier' ), 'error' );
			}
		}

		if ( isset( $_POST['submitwithslack'] ) ) {
			if ( '' !== $valid['slack_webhook_url'] ) {
				set_transient( 'sc_wpun_send_test_slack', 1 );
			} else {
				add_settings_error( 'sc_wpun_settings_email_notifications_slack_notifications', 'sc_wpun_settings_email_notifications_slack_notifications_error', __( 'Can not post test slack message. Slack settings are invalid.', 'wp-updates-notifier' ), 'error' );
			}
		}

		return $valid;
	}

	/**
	 * Output the text at the top of the main settings section (function is required even if it outputs nothing).
	 *
	 * @return void
	 */
	public function sc_wpun_settings_main_text() {
	}

	/**
	 * Settings field for frequency.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_main_field_frequency() {
		$frequency = SettingsContainer::get_instance()->get( 'frequency' );
		?>
		<select id="sc_wpun_settings_main_frequency" name="<?php echo esc_attr( self::OPT_FIELD ); ?>[frequency]">
		<?php foreach ( $this->get_schedules() as $k => $v ) : ?>
			<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $frequency, $k ); ?>><?php echo esc_html( $v['display'] ); ?></option>
		<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Simple sort function.
	 *
	 * @param  int $a Integer for sorting.
	 * @param  int $b Integer for sorting.
	 *
	 * @return int Frequency internval.
	 */
	private function sort_by_interval( $a, $b ) {
		return $a['interval'] - $b['interval'];
	}

	/**
	 * Get cron schedules.
	 *
	 * @return Array cron schedules.
	 */
	private function get_schedules() {
		$schedules = wp_get_schedules();
		uasort( $schedules, [ $this, 'sort_by_interval' ] );
		return $schedules;
	}

	/**
	 * Settings field for notify plugins.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_main_field_notify_plugins() {
		$settings = SettingsContainer::get_instance();
		?>
		<label><input name="<?php echo esc_attr( $settings->get_html_name( 'notify_plugins' ) ); ?>" type="radio" value="0" <?php checked( $settings->get( 'notify_plugins' ), 0 ); ?> /> <?php esc_html_e( 'No', 'wp-updates-notifier' ); ?>
		</label><br />
		<label><input name="<?php echo esc_attr( $settings->get_html_name( 'notify_plugins' ) ); ?>" type="radio" value="1" <?php checked( $settings->get( 'notify_plugins' ), 1 ); ?> /> <?php esc_html_e( 'Yes', 'wp-updates-notifier' ); ?>
		</label><br />
		<label><input name="<?php echo esc_attr( $settings->get_html_name( 'notify_plugins' ) ); ?>" type="radio" value="2" <?php checked( $settings->get( 'notify_plugins' ), 2 ); ?> /> <?php esc_html_e( 'Yes, but only active plugins', 'wp-updates-notifier' ); ?>
		</label>
		<?php
	}

	/**
	 * Settings field for notify themes.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_main_field_notify_themes() {
		$settings = SettingsContainer::get_instance();
		?>
		<label><input name="<?php echo esc_attr( $settings->get_html_name( 'notify_themes' ) ); ?>" type="radio" value="0" <?php checked( $settings->get( 'notify_themes' ), 0 ); ?> /> <?php esc_html_e( 'No', 'wp-updates-notifier' ); ?>
		</label><br />
		<label><input name="<?php echo esc_attr( $settings->get_html_name( 'notify_themes' ) ); ?>" type="radio" value="1" <?php checked( $settings->get( 'notify_themes' ), 1 ); ?> /> <?php esc_html_e( 'Yes', 'wp-updates-notifier' ); ?>
		</label><br />
		<label><input name="<?php echo esc_attr( $settings->get_html_name( 'notify_themes' ) ); ?>" type="radio" value="2" <?php checked( $settings->get( 'notify_themes' ), 2 ); ?> /> <?php esc_html_e( 'Yes, but only active themes', 'wp-updates-notifier' ); ?>
		</label>
		<?php
	}

	/**
	 * Settings field for notify core updates.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_main_field_notify_automatic() {
		$settings = SettingsContainer::get_instance();
		?>
		<label><input name="<?php echo esc_attr( $settings->get_html_name( 'notify_automatic' ) ); ?>" type="radio" value="0" <?php checked( $settings->get( 'notify_automatic' ), 0 ); ?> /> <?php esc_html_e( 'No', 'wp-updates-notifier' ); ?>
		</label><br />
		<label><input name="<?php echo esc_attr( $settings->get_html_name( 'notify_automatic' ) ); ?>" type="radio" value="1" <?php checked( $settings->get( 'notify_automatic' ), 1 ); ?> /> <?php esc_html_e( 'Yes', 'wp-updates-notifier' ); ?>
		</label>
		<?php
	}

	/**
	 * Settings field for hiding updates.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_main_field_hide_updates() {
		$settings = SettingsContainer::get_instance();
		?>
		<label><input name="<?php echo esc_attr( $settings->get_html_name( 'hide_updates' ) ); ?>" type="radio" value="0" <?php checked( $settings->get( 'hide_updates' ), 0 ); ?> /> <?php esc_html_e( 'No', 'wp-updates-notifier' ); ?>
		</label><br />
		<label><input name="<?php echo esc_attr( $settings->get_html_name( 'hide_updates' ) ); ?>" type="radio" value="1" <?php checked( $settings->get( 'hide_updates' ), 1 ); ?> /> <?php esc_html_e( 'Yes', 'wp-updates-notifier' ); ?>
		</label>
		<?php
	}

	/**
	 * Output the text at the top of the email settings section (function is required even if it outputs nothing).
	 *
	 * @return void
	 */
	public function sc_wpun_settings_email_notifications_text() {
	}

	/**
	 * Settings field for email notifications.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_email_notifications_field_email_notifications() {
		$settings = SettingsContainer::get_instance();
		?>
		<label><input name="<?php echo esc_attr( $settings->get_html_name( 'email_notifications' ) ); ?>" type="checkbox" value="1" <?php checked( $settings->get( 'email_notifications' ), 1 ); ?> /> <?php esc_html_e( 'Yes', 'wp-updates-notifier' ); ?>
		</label>
		<?php
	}

	/**
	 * Settings field for email to field.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_email_notifications_field_notify_to() {
		$settings = SettingsContainer::get_instance();
		$emails = $settings->get( 'notify_to' );
		$emails = ( ! empty( $emails ) ) ? implode( ',', $emails ) : '';

		?>
		<input id="sc_wpun_settings_email_notifications_notify_to" class="regular-text" name="<?php echo esc_attr( $settings->get_html_name( 'notify_to' ) ); ?>" value="<?php echo esc_attr( $emails ); ?>" />
		<span class="description"><?php esc_html_e( 'Separate multiple email address with a comma (,)', 'wp-updates-notifier' ); ?></span>
		<?php
	}

	/**
	 * Settings field for email from field.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_email_notifications_field_notify_from() {
		$settings = SettingsContainer::get_instance();
		?>
		<input id="sc_wpun_settings_email_notifications_notify_from" class="regular-text" name="<?php echo esc_attr( $settings->get_html_name( 'notify_from' ) ); ?>" value="<?php echo esc_attr( $settings->get( 'notify_from' ) ); ?>" />
		<?php
	}

	/**
	 * Output the text at the top of the slack settings section (function is required even if it outputs nothing).
	 *
	 * @return void
	 */
	public function sc_wpun_settings_slack_notifications_text() {
	}

	/**
	 * Settings field for slack notifications.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_slack_notifications_field_slack_notifications() {
		$settings = SettingsContainer::get_instance();
		?>
		<label><input name="<?php echo esc_attr( $settings->get_html_name( 'slack_notifications' ) ); ?>" type="checkbox" value="1" <?php checked( $settings->get( 'slack_notifications' ), 1 ); ?> /> <?php esc_html_e( 'Yes', 'wp-updates-notifier' ); ?>
		</label>
		<?php
	}

	/**
	 * Settings field for slack webhook url.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_slack_notifications_field_slack_webhook_url() {
		$settings = SettingsContainer::get_instance();
		?>
		<input id="sc_wpun_settings_slack_notifications_slack_webhook_url" class="regular-text" name="<?php echo esc_attr( $settings->get_html_name( 'slack_webhook_url' ) ); ?>" value="<?php echo esc_attr( $settings->get( 'slack_webhook_url' ) ); ?>" />
		<?php
	}

	/**
	 * Settings field for slack channel override.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_slack_notifications_field_slack_channel_override() {
		$settings = SettingsContainer::get_instance();
		?>
		<input id="sc_wpun_settings_slack_notifications_slack_channel_override" class="regular-text" name="<?php echo esc_attr( $settings->get_html_name( 'slack_channel_override' ) ); ?>" value="<?php echo esc_attr( $settings->get( 'slack_channel_override' ) ); ?>" />
		<span class="description"><?php esc_html_e( 'Not required.', 'wp-updates-notifier' ); ?></span>
		<?php
	}
}
