<?php

namespace Survos\EzBundle\Tests;

use PHPUnit\Framework\TestCase;
use Survos\EzBundle\SurvosEzBundle;

class SurvosEzBundleTest extends \TestCase
{
	public function testBundleExists(): void
	{
		$bundle = new SurvosEzBundle();
		$this->assertInstanceOf(SurvosEzBundle::class, $bundle);
	}
}
