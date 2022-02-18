<?php
/**
 * The notifier contract. All notifier classes MUST implement this contract.
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
}
