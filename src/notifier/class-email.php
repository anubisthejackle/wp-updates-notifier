<?php
/**
 * Notifier class for sending Email notifications.
 *
 * @package wp-updates-notifier
 */

namespace Notifier\Notifier;

use Notifier\Contracts\Notifier;

/**
 * The email notifier extension handles notifications VIA Email.
 */
class Email implements Notifier {

	/**
	 * An array of strings that should be used for parsing the
	 * updates into a message.
	 *
	 * @var string[]
	 */
	private $markup_vars = [
		'i_start'     => '<i>',
		'i_end'       => '</i>',
		'line_break'  => '<br>',
		'link_start'  => '<a href="',
		'link_middle' => '">',
		'link_end'    => '</a>',
		'b_start'     => '<b>',
		'b_end'       => '</b>',
	];

	/**
	 * Prepare the message.
	 *
	 * @param array $updates Array of all of the updates to notifiy about.
	 *
	 * @return string Message to be sent.
	 */
	public function prepare_message( $updates ): string {

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

	/**
	 * Send a test email.
	 *
	 * @return void
	 */
	public function send_test(): void {
		$reference_text = $this->markup_vars['line_break']
		. $this->markup_vars['b_start'] . esc_html( get_bloginfo() ) . $this->markup_vars['b_end'] . ' - '
		. $this->markup_vars['link_start'] . esc_url( home_url() ) . $this->markup_vars['link_middle']
		. esc_url( home_url() ) . $this->markup_vars['link_end'];

		$this->send_email_message(
			sprintf(
				__( 'This is a test message from WP Updates Notifier. %s', 'wp-updates-notifier' ),
				$reference_text
			)
		);
	}

	/**
	 * Get the from email address.
	 *
	 * @return String email address.
	 */
	public function sc_wpun_wp_mail_from() {
		$settings = $this->get_set_options( self::OPT_FIELD );
		return $settings['notify_from'];
	}

	/**
	 * Get the name to send email from.
	 *
	 * @return String From Name.
	 */
	public function sc_wpun_wp_mail_from_name() {
		return __( 'WP Updates Notifier', 'wp-updates-notifier' );
	}

	/**
	 * Email type.
	 *
	 * @return String email type.
	 */
	public function sc_wpun_wp_mail_content_type() {
		return 'text/html';
	}

	/**
	 * Sends email message.
	 *
	 * @param string $message Holds message to be sent in body of email.
	 *
	 * @return bool Whether the email contents were sent successfully.
	 */
	public function send_message( $message ): bool {
		$settings = $this->get_set_options( self::OPT_FIELD ); // get settings.

		/**
		 * Filters the email subject.
		 *
		 * Change the subject line that gets sent in the email.
		 *
		 * @since 1.6.1
		 *
		 * @param string  $subject Email subject line.
		 */
		$subject = sprintf( __( 'WP Updates Notifier: Updates Available @ %s', 'wp-updates-notifier' ), home_url() );
		$subject = apply_filters( 'sc_wpun_email_subject', $subject );

		add_filter( 'wp_mail_from', [ $this, 'sc_wpun_wp_mail_from' ] ); // add from filter.
		add_filter( 'wp_mail_from_name', [ $this, 'sc_wpun_wp_mail_from_name' ] ); // add from name filter.
		add_filter( 'wp_mail_content_type', [ $this, 'sc_wpun_wp_mail_content_type' ] ); // add content type filter.

		/**
		 * Filters the email content.
		 *
		 * Change the message that gets sent in the email.
		 *
		 * @since 1.6.1
		 *
		 * @param string  $message Email message.
		 */
		$message = apply_filters( 'sc_wpun_email_content', $message );

		$response = wp_mail( $settings['notify_to'], apply_filters( 'sc_wpun_email_subject', $subject ), apply_filters( 'sc_wpun_email_content', $message ) ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail

		remove_filter( 'wp_mail_from', [ $this, 'sc_wpun_wp_mail_from' ] ); // remove from filter.
		remove_filter( 'wp_mail_from_name', [ $this, 'sc_wpun_wp_mail_from_name' ] ); // remove from name filter.
		remove_filter( 'wp_mail_content_type', [ $this, 'sc_wpun_wp_mail_content_type' ] ); // remove content type filter.

		return $response;
	}
}
