<?php
/**
 * Publish to WP Updates Notifier Tests: SC_WP_Updates_Notifier class
 *
 * Contains a class which is used to test the SC_WP_Updates_Notifier class.
 *
 * @package SC_WP_Updates_Notifier
 * @subpackage Tests
 */

namespace Notifier\Tests\Unit;

use Notifier\Tests\Unit_Test_Case as TestCase;
/**
 * A class which is used to test the SC_WP_Updates_Notifier class.
 */
class Unit_Tests extends TestCase {
	public function test_unit_test() {
		$this->assertFalse( function_exists( 'add_filter' ) );
	}
}
