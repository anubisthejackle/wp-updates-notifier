<?php
/**
 * Notifier class for sending Slack notifications.
 */

namespace Notifier\Notifier;

use Notifier\Contracts\Notifier;

class Slack implements Notifier {

	private $markup_vars = [
		'i_start'     => '_',
		'i_end'       => '_',
		'line_break'  => '
',
		'link_start'  => '<',
		'link_middle' => '|',
		'link_end'    => '>',
		'b_start'     => '*',
		'b_end'       => '*',
	];

	/**
	 * Send a test slack message.
	 *
	 * @return void
	 */
	public function send_test(): void {
		$reference_text = $this->markup_vars['line_break']
		. $this->markup_vars['b_start'] . esc_html( get_bloginfo() ) . $this->markup_vars['b_end'] . ' - '
		. $this->markup_vars['link_start'] . esc_url( home_url() ) . $this->markup_vars['link_middle']
		. esc_url( home_url() ) . $this->markup_vars['link_end'];

		$this->send_message(
			sprintf(
				__( 'This is a test message from WP Updates Notifier. %s', 'wp-updates-notifier' ),
				$reference_text
			)
		);
	}

	/**
	 * Sends slack post.
	 *
	 * @param string $message Holds message to be posted to slack.
	 *
	 * @return bool Success or failure.
	 */
	public function send_message( string $message ): bool {
		$settings = $this->get_set_options( self::OPT_FIELD ); // get settings

		/**
		 * Filters the Slack username.
		 *
		 * Change the username that is used to post to Slack.
		 *
		 * @since 1.6.1
		 *
		 * @param string  $username Username string.
		 */
		$username = __( 'WP Updates Notifier', 'wp-updates-notifier' );
		$username = apply_filters( 'sc_wpun_slack_username', $username );

		/**
		 * Filters the Slack user icon.
		 *
		 * Change the user icon that is posted to Slack.
		 *
		 * @since 1.6.1
		 *
		 * @param string  $user_icon Emoji string.
		 */
		$user_icon = ':robot_face:';
		$user_icon = apply_filters( 'sc_wpun_slack_user_icon', $user_icon );

		/**
		 * Filters the slack message content.
		 *
		 * Change the message content that is posted to Slack.
		 *
		 * @since 1.6.1
		 *
		 * @param String  $message Message posted to Slack.
		 */
		$message = apply_filters( 'sc_wpun_slack_content', $message );

		$payload = array(
			'username'   => $username,
			'icon_emoji' => $user_icon,
			'text'       => $message,
		);

		if ( ! empty( $settings['slack_channel_override'] ) && '' !== $settings['slack_channel_override'] ) {
			$payload['channel'] = $settings['slack_channel_override'];
		}

		/**
		 * Filters the Slack channel.
		 *
		 * Change the Slack channel to post to.
		 *
		 * @since 1.6.1
		 *
		 * @param string  $payload['channel'] Slack channel.
		 */
		$payload['channel'] = apply_filters( 'sc_wpun_slack_channel', $payload['channel'] );

		/**
		 * Filters the Slack webhook url.
		 *
		 * Change the webhook url that is called by the plugin to post to Slack.
		 *
		 * @since 1.6.1
		 *
		 * @param string  $settings['slack_webhook_url'] Webhook url.
		 */
		$slack_webhook_url = apply_filters( 'sc_wpun_slack_webhook_url', $settings['slack_webhook_url'] );

		$response = wp_remote_post(
			$slack_webhook_url,
			array(
				'method' => 'POST',
				'body'   => array(
					'payload' => wp_json_encode( $payload ),
				),
			)
		);

		return is_wp_error( $response );
	}

	/**
	 * Prepare the message.
	 *
	 * @param array $updates Array of all of the updates to notifiy about.
	 * @param array $markup_vars Array of the markup characters to use.
	 *
	 * @return string Message to be sent.
	 */
	public function prepare_message( $updates ) {

		$markup_vars = $this->markup_vars;
		/**
		 * Filters the message text intro.
		 *
		 * @param string $message Intro message text.
		 */
		$message = $markup_vars['i_start'] . esc_html( apply_filters( 'sc_wpun_message_text', __( 'Updates Available', 'wp-updates-notifier' ) ) )
		. $markup_vars['i_end'] . $markup_vars['line_break'] . $markup_vars['b_start']
		. esc_html( get_bloginfo() ) . $markup_vars['b_end'] . ' - '
		. $markup_vars['link_start'] . esc_url( home_url() ) . $markup_vars['link_middle']
		. esc_url( home_url() ) . $markup_vars['link_end'] . $markup_vars['line_break'];

		if ( ! empty( $updates['core'] ) ) {
			$message .= $markup_vars['line_break'] . $markup_vars['b_start'] . $markup_vars['link_start']
			. esc_url( admin_url( 'update-core.php' ) ) . $markup_vars['link_middle']
			. esc_html( __( 'WordPress Core', 'wp-updates-notifier' ) ) . $markup_vars['link_end']
			. $markup_vars['b_end'] . ' (' . $updates['core']['old_version'] . esc_html( __( ' to ', 'wp-updates-notifier' ) )
			. $updates['core']['new_version'] . ')' . $markup_vars['line_break'];
		}

		if ( ! empty( $updates['plugin'] ) ) {
			$message .= $markup_vars['line_break'] . $markup_vars['b_start'] . $markup_vars['link_start']
			. esc_url( admin_url( 'plugins.php?plugin_status=upgrade' ) ) . $markup_vars['link_middle']
			. esc_html( __( 'Plugin Updates', 'wp-updates-notifier' ) ) . $markup_vars['link_end']
			. $markup_vars['b_end'] . $markup_vars['line_break'];

			foreach ( $updates['plugin'] as $plugin ) {
				$message .= '	' . $plugin['name'];
				if ( ! empty( $plugin['old_version'] ) && ! empty( $plugin['new_version'] ) ) {
					$message .= ' (' . $plugin['old_version'] . esc_html( __( ' to ', 'wp-updates-notifier' ) )
					. $markup_vars['link_start'] . esc_url( $plugin['changelog_url'] ) . $markup_vars['link_middle']
					. $plugin['new_version'] . $markup_vars['link_end'] . ')' . $markup_vars['line_break'];
				}
			}
		}

		if ( ! empty( $updates['theme'] ) ) {
			$message .= $markup_vars['line_break'] . $markup_vars['b_start'] . $markup_vars['link_start']
			. esc_url( admin_url( 'themes.php' ) ) . $markup_vars['link_middle'] . esc_html( __( 'Theme Updates', 'wp-updates-notifier' ) )
			. $markup_vars['link_end'] . $markup_vars['b_end'] . $markup_vars['line_break'];

			foreach ( $updates['theme'] as $theme ) {
				$message .= '	' . $theme['name'];
				if ( ! empty( $theme['old_version'] ) && ! empty( $theme['new_version'] ) ) {
					$message .= ' (' . $theme['old_version'] . esc_html( __( ' to ', 'wp-updates-notifier' ) )
					. $theme['new_version'] . ')' . $markup_vars['line_break'];
				}
			}
		}

		return $message;
	}
}
