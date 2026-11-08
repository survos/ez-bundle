<?php

declare(strict_types=1);

namespace Survos\EzBundle\Tests;

use Survos\EzBundle\SurvosEzBundle;

class SurvosEzBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBundleExists(): void
    {
        $bundle = new SurvosEzBundle();
        $this->assertInstanceOf(SurvosEzBundle::class, $bundle);
    }
}
