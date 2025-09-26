<?php

namespace Survos\SurvosEzBundle\Tests;

use PHPUnit\Framework\TestCase;
use Survos\SurvosEzBundle\SurvosEzBundle;

class SurvosEzBundleTest extends \TestCase
{
	public function testBundleExists(): void
	{
		$bundle = new SurvosEzBundle();
		$this->assertInstanceOf(SurvosEzBundle::class, $bundle);
	}
}
