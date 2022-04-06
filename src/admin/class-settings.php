<?php
/**
 * Class for the WP Updates Notifier settings page.
 */

namespace Notifier\Admin;

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
	public static function boot(): void {
		$settings = new self();
		add_action( 'init', [ $settings, 'settings_up_to_date' ] );
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
	 * Check if this plugin settings are up to date. Firstly check the version in
	 * the DB. If they don't match then load in defaults but don't override values
	 * already set. Also this will remove obsolete settings that are not needed.
	 *
	 * @return void
	 */
	public function settings_up_to_date() {
		$current_ver = $this->get_set_options( self::OPT_VERSION_FIELD ); // Get current plugin version
		if ( self::OPT_VERSION !== $current_ver ) { // is the version the same as this plugin?
			$options = (array) get_option( self::OPT_FIELD ); // get current settings from DB

			// Get the default settings from the CONST
			$defaults = self::DEFAULT_SETTINGS;

			// If we are upgrading from settings before settings version 7, turn on email notifications by default.
			if ( intval( $current_ver ) > 0 && intval( $current_ver ) < 7 ) {
				$defaults['email_notifications'] = 1;
			}

			// Intersect current options with defaults. Basically removing settings that are obsolete
			$options = array_intersect_key( $options, $defaults );
			// Merge current settings with defaults. Basically adding any new settings with defaults that we dont have.
			$options = array_merge( $defaults, $options );
			$this->get_set_options( self::OPT_FIELD, $options ); // update settings
			$this->get_set_options( self::OPT_VERSION_FIELD, self::OPT_VERSION ); // update settings version
		}
	}

	/**
	 * Filter for when getting or settings this plugins settings
	 *
	 * @param string     $field    Option field name of where we are getting or setting plugin settings.
	 * @param bool|mixed $settings False if getting settings else an array with settings you are saving.
	 *
	 * @return bool|mixed True or false if setting or an array of settings if getting
	 */
	private function get_set_options( $field, $settings = false ) {
		if ( false === $settings ) {
			return apply_filters( 'sc_wpun_get_options_filter', get_option( $field ), $field );
		}
		return update_option( $field, apply_filters( 'sc_wpun_put_options_filter', $settings, $field ) );
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
		register_setting( self::OPT_FIELD, self::OPT_FIELD, [ $this, 'sc_wpun_settings_validate' ] ); // Register Settings

		add_settings_section( 'sc_wpun_settings_main', __( 'Settings', 'wp-updates-notifier' ), [ $this, 'sc_wpun_settings_main_text' ], 'wp-updates-notifier' ); // Make settings main section
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
		$settings = $this->get_set_options( self::OPT_FIELD ); // get settings
		if ( 1 === $settings['hide_updates'] ) { // is this enabled?
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
	// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	public function manage_plugins_custom_column( $column_name, $plugin_file, $plugin_data ) {
		$options = $this->get_set_options( self::OPT_FIELD ); // get settings
		if ( 1 === $options['notify_plugins'] ) {
			if ( 'update_notifications' === $column_name ) {
				if ( is_plugin_active( $plugin_file ) ) {
					if ( isset( $options['disabled_plugins'][ $plugin_file ] ) ) {
						echo '<button class="sc_wpun_btn sc_wpun_btn_disable" data-toggle="enable" data-file="' . esc_attr( $plugin_file ) . '">' . esc_attr( __( 'Notifications Disabled', 'wp-updates-notifier' ) ) . '</button>';
					} else {
						echo '<button class="sc_wpun_btn sc_wpun_btn_enable" data-toggle="disable" data-file="' . esc_attr( $plugin_file ) . '">' . esc_attr( __( 'Notifications Enabled', 'wp-updates-notifier' ) ) . '</button>';
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
		$options = $this->get_set_options( self::OPT_FIELD ); // get settings
		if ( 1 === $options['notify_plugins'] ) {
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
		$options = $this->get_set_options( self::OPT_FIELD ); // get settings
		if ( 1 === $options['notify_plugins'] ) {
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
			$plugin_file = sanitize_text_field( wp_unslash( $_POST['plugin_file'] ) );
			$toggle      = sanitize_text_field( wp_unslash( $_POST['toggle'] ) );
			$options     = $this->get_set_options( self::OPT_FIELD ); // get settings

			if ( 'disable' === $toggle ) {
				$options['disabled_plugins'][ $plugin_file ] = 1;
				echo 'success';
			} elseif ( 'enable' === $toggle ) {
				unset( $options['disabled_plugins'][ $plugin_file ] );
				echo 'success';
			} else {
				echo 'failure';
			}
			$this->get_set_options( self::OPT_FIELD, $options ); // update settings
		}
		die();
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

		$options     = $this->get_set_options( self::OPT_FIELD );
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Updates Notifier', 'wp-updates-notifier' ); ?></h2>

			<p>
				<span class="description">
				<?php
				if ( empty( $options['last_check_time'] ) ) {
					$scan_date = __( 'Never', 'wp-updates-notifier' );
				} else {
					$scan_date = sprintf(
						__( '%1$1s @ %2$2s', 'wp-updates-notifier' ),
						gmdate( $date_format, $options['last_check_time'] ),
						gmdate( $time_format, $options['last_check_time'] )
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
				<input class="button-primary" name="Submit" type="submit" value="<?php esc_html_e( 'Save settings', 'wp-updates-notifier' ); ?>" />
				<input class="button" name="submitwithemail" type="submit" value="<?php esc_html_e( 'Save settings with test email', 'wp-updates-notifier' ); ?>" />
				<input class="button" name="submitwithslack" type="submit" value="<?php esc_html_e( 'Save settings with test slack post', 'wp-updates-notifier' ); ?>" />
				<br><br>
				<input class="button" name="restoredefaults" type="submit" value="<?php esc_html_e( 'Restore Default Settings', 'wp-updates-notifier' ); ?>" />
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
		// disabled plugins will only be set through the plugins page, so we only check the admin referer for the options page if they aren't set
		if ( ! isset( $input['disabled_plugins'] ) ) {
			check_admin_referer( 'sc_wpun_settings-options' );
		}
		$valid = $this->get_set_options( self::OPT_FIELD );

		// Validate main settings.
		if ( in_array( $input['frequency'], $this->get_intervals(), true ) ) {
			$valid['frequency'] = $input['frequency'];
			do_action( 'sc_wpun_enable_cron', $input['frequency'] );
		} else {
			add_settings_error( 'sc_wpun_settings_main_frequency', 'sc_wpun_settings_main_frequency_error', __( 'Invalid frequency entered', 'wp-updates-notifier' ), 'error' );
		}

		$sanitized_notify_plugins = absint( isset( $input['notify_plugins'] ) ? $input['notify_plugins'] : 0 );
		if ( $sanitized_notify_plugins >= 0 && $sanitized_notify_plugins <= 1 ) {
			$valid['notify_plugins'] = $sanitized_notify_plugins;
		} else {
			add_settings_error( 'sc_wpun_settings_main_notify_plugins', 'sc_wpun_settings_main_notify_plugins_error', __( 'Invalid plugin updates value entered', 'wp-updates-notifier' ), 'error' );
		}

		$sanitized_notify_themes = absint( isset( $input['notify_themes'] ) ? $input['notify_themes'] : 0 );
		if ( $sanitized_notify_themes >= 0 && $sanitized_notify_themes <= 1 ) {
			$valid['notify_themes'] = $sanitized_notify_themes;
		} else {
			add_settings_error( 'sc_wpun_settings_main_notify_themes', 'sc_wpun_settings_main_notify_themes_error', __( 'Invalid theme updates value entered', 'wp-updates-notifier' ), 'error' );
		}

		$sanitized_notify_automatic = absint( isset( $input['notify_automatic'] ) ? $input['notify_automatic'] : 0 );
		if ( $sanitized_notify_automatic >= 0 && $sanitized_notify_automatic <= 1 ) {
			$valid['notify_automatic'] = $sanitized_notify_automatic;
		} else {
			add_settings_error( 'sc_wpun_settings_main_notify_automatic', 'sc_wpun_settings_main_notify_automatic_error', __( 'Invalid automatic updates value entered', 'wp-updates-notifier' ), 'error' );
		}

		$sanitized_hide_updates = absint( isset( $input['hide_updates'] ) ? $input['hide_updates'] : 0 );
		if ( $sanitized_hide_updates <= 1 ) {
			$valid['hide_updates'] = $sanitized_hide_updates;
		} else {
			add_settings_error( 'sc_wpun_settings_main_hide_updates', 'sc_wpun_settings_main_hide_updates_error', __( 'Invalid hide updates value entered', 'wp-updates-notifier' ), 'error' );
		}

		// Validate email notification settings.
		if ( ! empty( $input['notify_to'] ) ) {
			$emails_to = explode( ',', $input['notify_to'] );
			if ( $emails_to ) {
				$sanitized_emails = [];
				$was_error        = false;
				foreach ( $emails_to as $email_to ) {
					$address = sanitize_email( trim( $email_to ) );
					if ( ! is_email( $address ) ) {
						add_settings_error( 'sc_wpun_settings_email_notifications_notify_to', 'sc_wpun_settings_email_notifications_notify_to_error', __( 'One or more email to addresses are invalid', 'wp-updates-notifier' ), 'error' );
						$was_error = true;
						break;
					}
					$sanitized_emails[] = $address;
				}
				if ( ! $was_error ) {
					$valid['notify_to'] = implode( ',', $sanitized_emails );
				}
			}
		} else {
			$valid['notify_to'] = '';
		}

		if ( ! empty( $input['notify_from'] ) ) {
			$sanitized_email_from = sanitize_email( $input['notify_from'] );
			if ( is_email( $sanitized_email_from ) ) {
				$valid['notify_from'] = $sanitized_email_from;
			} else {
				add_settings_error( 'sc_wpun_settings_email_notifications_notify_from', 'sc_wpun_settings_email_notifications_notify_from_error', __( 'Invalid email from entered', 'wp-updates-notifier' ), 'error' );
			}
		} else {
			$valid['notify_from'] = '';
		}

		$email_notifications = absint( isset( $input['email_notifications'] ) ? $input['email_notifications'] : 0 );
		if ( 1 < $email_notifications ) {
			add_settings_error( 'sc_wpun_settings_email_notifications_email_notifications', 'sc_wpun_settings_email_notifications_email_notifications_error', __( 'Invalid notification email value entered', 'wp-updates-notifier' ), 'error' );
		}

		if ( 1 === $email_notifications ) {
			if ( ! empty( $valid['notify_to'] ) && ! empty( $valid['notify_from'] ) ) {
				$email_notifications = 1;
			} else {
				add_settings_error( 'sc_wpun_settings_email_notifications_notify_from', 'sc_wpun_settings_email_notifications_notify_to_error', __( 'Can not enable email notifications, addresses are not valid', 'wp-updates-notifier' ), 'error' );
				$email_notifications = 0;
			}
		}
		$valid['email_notifications'] = $email_notifications;

		$active_plugins            = array_flip( get_option( 'active_plugins' ) );
		$valid['disabled_plugins'] = [];
		if ( ! empty( $input['disabled_plugins'] ) ) {
			foreach ( $input['disabled_plugins'] as $new_disabled_plugin => $val ) {
				if ( isset( $active_plugins[ $new_disabled_plugin ] ) ) {
					$valid['disabled_plugins'][ $new_disabled_plugin ] = 1;
				}
			}
		}

		// Validate slack settings.
		if ( ! empty( $input['slack_webhook_url'] ) ) {
			if ( false === filter_var( $input['slack_webhook_url'], FILTER_VALIDATE_URL ) ) {
				add_settings_error( 'sc_wpun_settings_slack_notifications_slack_webhook_url', 'sc_wpun_settings_slack_notifications_slack_webhook_url_error', __( 'Invalid webhook url entered', 'wp-updates-notifier' ), 'error' );
			} else {
				$valid['slack_webhook_url'] = $input['slack_webhook_url'];
			}
		} else {
			$valid['slack_webhook_url'] = '';
		}

		if ( ! empty( $input['slack_channel_override'] ) ) {
			if ( '#' !== substr( $input['slack_channel_override'], 0, 1 ) && '@' !== substr( $input['slack_channel_override'], 0, 1 ) ) {
				add_settings_error( 'sc_wpun_settings_slack_notifications_slack_channel_override', 'sc_wpun_settings_slack_notifications_slack_channel_override_error', __( 'Channel name must start with a # or @', 'wp-updates-notifier' ), 'error' );
			} elseif ( strpos( $input['slack_channel_override'], ' ' ) ) {
				add_settings_error( 'sc_wpun_settings_slack_notifications_slack_channel_override', 'sc_wpun_settings_slack_notifications_slack_channel_override_error', __( 'Channel name must not contain a space', 'wp-updates-notifier' ), 'error' );
			} else {
				$valid['slack_channel_override'] = $input['slack_channel_override'];
			}
		} else {
			$valid['slack_channel_override'] = '';
		}

		$slack_notifications = absint( isset( $input['slack_notifications'] ) ? $input['slack_notifications'] : 0 );
		if ( $slack_notifications > 1 ) {
			add_settings_error( 'sc_wpun_settings_slack_notifications_slack_notifications', 'sc_wpun_settings_slack_notifications_slack_notifications_error', __( 'Invalid notification slack value entered', 'wp-updates-notifier' ), 'error' );
		}

		if ( 1 === $slack_notifications ) {
			if ( '' === $valid['slack_webhook_url'] ) {
				add_settings_error( 'sc_wpun_settings_slack_notifications_slack_webhook_url', 'sc_wpun_settings_slack_notifications_slack_webhook_url_error', __( 'No to slack webhoook url entered', 'wp-updates-notifier' ), 'error' );
				$slack_notifications = 0;
			} else {
				$slack_notifications = 1;
			}
		} else {
			$slack_notifications = 0;
		}
		$valid['slack_notifications'] = $slack_notifications;

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

		if ( isset( $_POST['restoredefaults'] ) ) {
			// override all settings
			$valid = self::DEFAULT_SETTINGS;
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
		$options = $this->get_set_options( self::OPT_FIELD );
		?>
		<select id="sc_wpun_settings_main_frequency" name="<?php echo esc_attr( self::OPT_FIELD ); ?>[frequency]">
		<?php foreach ( $this->get_schedules() as $k => $v ) : ?>
			<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $options['frequency'], $k ); ?>><?php echo esc_html( $v['display'] ); ?></option>
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
		$options = $this->get_set_options( self::OPT_FIELD );
		?>
		<label><input name="<?php echo esc_attr( self::OPT_FIELD ); ?>[notify_plugins]" type="radio" value="0" <?php checked( $options['notify_plugins'], 0 ); ?> /> <?php esc_html_e( 'No', 'wp-updates-notifier' ); ?>
		</label><br />
		<label><input name="<?php echo esc_attr( self::OPT_FIELD ); ?>[notify_plugins]" type="radio" value="1" <?php checked( $options['notify_plugins'], 1 ); ?> /> <?php esc_html_e( 'Yes', 'wp-updates-notifier' ); ?>
		</label><br />
		<label><input name="<?php echo esc_attr( self::OPT_FIELD ); ?>[notify_plugins]" type="radio" value="2" <?php checked( $options['notify_plugins'], 2 ); ?> /> <?php esc_html_e( 'Yes, but only active plugins', 'wp-updates-notifier' ); ?>
		</label>
		<?php
	}

	/**
	 * Settings field for notify themes.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_main_field_notify_themes() {
		$options = $this->get_set_options( self::OPT_FIELD );
		?>
		<label><input name="<?php echo esc_attr( self::OPT_FIELD ); ?>[notify_themes]" type="radio" value="0" <?php checked( $options['notify_themes'], 0 ); ?> /> <?php esc_html_e( 'No', 'wp-updates-notifier' ); ?>
		</label><br />
		<label><input name="<?php echo esc_attr( self::OPT_FIELD ); ?>[notify_themes]" type="radio" value="1" <?php checked( $options['notify_themes'], 1 ); ?> /> <?php esc_html_e( 'Yes', 'wp-updates-notifier' ); ?>
		</label><br />
		<label><input name="<?php echo esc_attr( self::OPT_FIELD ); ?>[notify_themes]" type="radio" value="2" <?php checked( $options['notify_themes'], 2 ); ?> /> <?php esc_html_e( 'Yes, but only active themes', 'wp-updates-notifier' ); ?>
		</label>
		<?php
	}

	/**
	 * Settings field for notify core updates.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_main_field_notify_automatic() {
		$options = $this->get_set_options( self::OPT_FIELD );
		?>
		<label><input name="<?php echo esc_attr( self::OPT_FIELD ); ?>[notify_automatic]" type="radio" value="0" <?php checked( $options['notify_automatic'], 0 ); ?> /> <?php esc_html_e( 'No', 'wp-updates-notifier' ); ?>
		</label><br />
		<label><input name="<?php echo esc_attr( self::OPT_FIELD ); ?>[notify_automatic]" type="radio" value="1" <?php checked( $options['notify_automatic'], 1 ); ?> /> <?php esc_html_e( 'Yes', 'wp-updates-notifier' ); ?>
		</label>
		<?php
	}

	/**
	 * Settings field for hiding updates.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_main_field_hide_updates() {
		$options = $this->get_set_options( self::OPT_FIELD );
		?>
		<label><input name="<?php echo esc_attr( self::OPT_FIELD ); ?>[hide_updates]" type="radio" value="0" <?php checked( $options['hide_updates'], 0 ); ?> /> <?php esc_html_e( 'No', 'wp-updates-notifier' ); ?>
		</label><br />
		<label><input name="<?php echo esc_attr( self::OPT_FIELD ); ?>[hide_updates]" type="radio" value="1" <?php checked( $options['hide_updates'], 1 ); ?> /> <?php esc_html_e( 'Yes', 'wp-updates-notifier' ); ?>
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
		$options = $this->get_set_options( self::OPT_FIELD );
		?>
		<label><input name="<?php echo esc_attr( self::OPT_FIELD ); ?>[email_notifications]" type="checkbox" value="1" <?php checked( $options['email_notifications'], 1 ); ?> /> <?php esc_html_e( 'Yes', 'wp-updates-notifier' ); ?>
		</label>
		<?php
	}

	/**
	 * Settings field for email to field.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_email_notifications_field_notify_to() {
		$options = $this->get_set_options( self::OPT_FIELD );
		?>
		<input id="sc_wpun_settings_email_notifications_notify_to" class="regular-text" name="<?php echo esc_attr( self::OPT_FIELD ); ?>[notify_to]" value="<?php echo esc_attr( $options['notify_to'] ); ?>" />
		<span class="description"><?php esc_html_e( 'Separate multiple email address with a comma (,)', 'wp-updates-notifier' ); ?></span>
		<?php
	}

	/**
	 * Settings field for email from field.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_email_notifications_field_notify_from() {
		$options = $this->get_set_options( self::OPT_FIELD );
		?>
		<input id="sc_wpun_settings_email_notifications_notify_from" class="regular-text" name="<?php echo esc_attr( self::OPT_FIELD ); ?>[notify_from]" value="<?php echo esc_attr( $options['notify_from'] ); ?>" />
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
		$options = $this->get_set_options( self::OPT_FIELD );
		?>
		<label><input name="<?php echo esc_attr( self::OPT_FIELD ); ?>[slack_notifications]" type="checkbox" value="1" <?php checked( $options['slack_notifications'], 1 ); ?> /> <?php esc_html_e( 'Yes', 'wp-updates-notifier' ); ?>
		</label>
		<?php
	}

	/**
	 * Settings field for slack webhook url.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_slack_notifications_field_slack_webhook_url() {
		$options = $this->get_set_options( self::OPT_FIELD );
		?>
		<input id="sc_wpun_settings_slack_notifications_slack_webhook_url" class="regular-text" name="<?php echo esc_attr( self::OPT_FIELD ); ?>[slack_webhook_url]" value="<?php echo esc_attr( $options['slack_webhook_url'] ); ?>" />
		<?php
	}

	/**
	 * Settings field for slack channel override.
	 *
	 * @return void
	 */
	public function sc_wpun_settings_slack_notifications_field_slack_channel_override() {
		$options = $this->get_set_options( self::OPT_FIELD );
		?>
		<input id="sc_wpun_settings_slack_notifications_slack_channel_override" class="regular-text" name="<?php echo esc_attr( self::OPT_FIELD ); ?>[slack_channel_override]" value="<?php echo esc_attr( $options['slack_channel_override'] ); ?>" />
		<span class="description"><?php esc_html_e( 'Not required.', 'wp-updates-notifier' ); ?></span>
		<?php
	}
}
