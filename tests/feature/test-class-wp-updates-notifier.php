<?php
/**
 * Publish to WP Updates Notifier Tests: SC_WP_Updates_Notifier class
 *
 * Contains a class which is used to test the SC_WP_Updates_Notifier class.
 *
 * @package SC_WP_Updates_Notifier
 * @subpackage Tests
 */

namespace Notifier\Tests\Feature;

use Notifier\Tests\Integration_Test_Case as TestCase;
/**
 * A class which is used to test the SC_WP_Updates_Notifier class.
 */
class SC_WP_Updates_Notifier_Tests extends TestCase {

	/**
	 * Example test.
	 */
	public function test_example() {
		$exists = function_exists( 'add_action' );
		$this->assertTrue( $exists );
	}
}
