<?php
/**
 * Notifier class for sending Slack notifications.
 */

namespace Notifier\Notifier;

class Slack {

	const MARKUP_VARS_SLACK = array(
		'i_start'     => '_',
		'i_end'       => '_',
		'line_break'  => '
',
		'link_start'  => '<',
		'link_middle' => '|',
		'link_end'    => '>',
		'b_start'     => '*',
		'b_end'       => '*',
	);

	/**
	 * Send a test slack message.
	 *
	 * @return void
	 */
	public function send_test_slack( $markup_vars ) {
		$reference_text = $markup_vars['line_break']
		. $markup_vars['b_start'] . esc_html( get_bloginfo() ) . $markup_vars['b_end'] . ' - '
		. $markup_vars['link_start'] . esc_url( home_url() ) . $markup_vars['link_middle']
		. esc_url( home_url() ) . $markup_vars['link_end'];

		$this->send_slack_message(
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
	public function send_slack_message( $message ) {
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
}
