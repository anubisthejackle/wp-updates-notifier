<?php
/**
 * The main notifier class. This class calls out to all the individual notifier classes.
 *
 * @package wp-updates-notifier
 */

namespace Notifier;

use Notifier\Contracts\Notifier as NotifierContract;
use Notifier\Notifier\Email;
use Notifier\Notifier\Slack;

class Notifier {

	private $notifiers = [];

	public static function boot(): void {

		$notifier = new self();
		if ( 1 === $options['email_notifications'] ) {
			$notifier->add_notifier( new Email() );
		}

		if ( 1 === $options['slack_notifications'] ) {
			$notifier->add_notifier( new Slack() );
		}
	}

	public function add_notifier( NotifierContract $notifier ) {
		$this->notifiers[] = $notifier;
	}

	public function send_message() {

		foreach ( $this->notifiers as $notifier ) {
			$message = $notifier->prepare_message( $updates );
			$notifier->send_message( $message );
		}

	}

}
