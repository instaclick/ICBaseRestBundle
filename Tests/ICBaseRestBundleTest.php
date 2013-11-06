<?php
/**
 * @copyright 2013 Instaclick Inc.
 */
namespace IC\Bundle\Base\RestBundle\Tests;

use IC\Bundle\Base\TestBundle\Test\BundleTestCase;
use IC\Bundle\Base\RestBundle\ICBaseRestBundle;

/**
 * Test the entity getters and setters
 *
 * @group ICBaseRestBundle
 * @group Unit
 *
 * @author Kinn Coelho Julião <kinnj@nationalfibre.net>
 *
 */
class ICBaseRestBundleTest extends BundleTestCase
{
    /**
     * Should build the bundle
     */
    public function testShouldBuildBundle()
    {
        $bundle = new ICBaseRestBundle();

        $bundle->build($this->container);

        $config = $this->container->getCompilerPassConfig();
        $passes = $config->getBeforeOptimizationPasses();

        $this->assertEmpty($passes);

    }
}
