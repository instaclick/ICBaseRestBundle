<?php
/**
 * @copyright 2013 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Tests\DependencyInjection;

use IC\Bundle\Base\TestBundle\Test\DependencyInjection\ExtensionTestCase;
use IC\Bundle\Base\RestBundle\DependencyInjection\ICBaseRestExtension;

/**
 * Test for ICBaseRestExtension
 *
 * @group ICBaseRestBundle
 * @group Unit
 * @group DependencyInjection
 *
 * @author John Zhang <johnz@nationalfibre.net>
 */
class ICBaseRestExtensionTest extends ExtensionTestCase
{
    /**
     * Test configuration
     */
    public function testConfiguration()
    {
        $loader = new ICBaseRestExtension();

        $this->load($loader, array());
    }
}
