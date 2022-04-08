<?php

namespace Notifier\Tests;

use PHPUnit\Framework\TestCase;

abstract class Unit_Test_Case extends TestCase {

	public function __construct(...$args){
		parent::__construct( ...$args );

		$this->setPreserveGlobalState(false);
		$this->setRunClassInSeparateProcess(true);
	}

}
