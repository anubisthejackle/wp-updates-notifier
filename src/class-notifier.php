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
use Notifier\Settings;

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
		if ( 1 === Settings::get_instance()->get( 'email_notifications' ) ) {
			$notifier->add_notifier( new Email() );
		}

		if ( 1 === Settings::get_instance()->get( 'slack_notifications' ) ) {
			$notifier->add_notifier( new Slack() );
		}

		add_action( 'notifier_send_notification', [ $notifier, 'send_message' ] );
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
	 *
	 * @param array $updates The array of updates to notify about.
	 */
	public function send_message( $updates ): void {
		foreach ( $this->notifiers as $notifier ) {
			$message = $notifier->prepare_message( $updates );
			$notifier->send_message( $message );
		}
	}
}
