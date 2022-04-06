<?php
/**
 * The notifier contract. All notifier classes MUST implement this contract.
 *
 * @package wp-updates-notifier
 */

namespace Notifier\Contracts;

interface Notifier {
	/**
	 * Send a test message.
	 *
	 * @return void
	 */
	public function send_test(): void;

	/**
	 * Used to send notification messages.
	 *
	 * @param string $message The message to send.
	 * @return bool True if successful, false otherwise.
	 */
	public function send_message( string $message ): bool;

	/**
	 * Used to format the message for sending.
	 *
	 * @param array $updates The array of updates to prepare a message from.
	 * @return string The message to send.
	 */
	public function prepare_message( $updates ): string;
}
