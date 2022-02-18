<?php
/**
 * The main notifier class. This class calls out to all the individual notifier classes.
 */

namespace Notifier;

class Notifier {

	public function send_message() {
		// Send email notification.
		if ( 1 === $options['email_notifications'] ) {
			$message = $this->prepare_message( $updates, self::MARKUP_VARS_EMAIL );
			$this->send_email_message( $message );
		}

		// Send slack notification.
		if ( 1 === $options['slack_notifications'] ) {
			$message = $this->prepare_message( $updates, self::MARKUP_VARS_SLACK );
			$this->send_slack_message( $message );
		}
	}

}
