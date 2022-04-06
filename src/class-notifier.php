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

/**
 * The Notifier class is the root of the notification logic. It loads, and executes,
 * the Notifier extensions.
 */
class Notifier {

	/**
	 * The collection of Notifier contracts.
	 *
	 * @var array
	 */
	private $notifiers = [];

	/**
	 * Initialize the notification extensions.
	 */
	public static function boot(): void {

		$notifier = new self();
		if ( 1 === $options['email_notifications'] ) {
			$notifier->add_notifier( new Email() );
		}

		if ( 1 === $options['slack_notifications'] ) {
			$notifier->add_notifier( new Slack() );
		}
	}

	/**
	 * Adds a notifier to the notifiers array.
	 *
	 * @param \NotifierContract $notifier An object that implements the Notifier contract.
	 */
	public function add_notifier( NotifierContract $notifier ) {
		$this->notifiers[] = $notifier;
	}

	/**
	 * Perform the actual message sending for all loaded notifier extensions.
	 */
	public function send_message() {
		foreach ( $this->notifiers as $notifier ) {
			$message = $notifier->prepare_message( $updates );
			$notifier->send_message( $message );
		}
	}
}
