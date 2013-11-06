<?php
/**
 * @copyright 2012 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Tests\DataFixtures\ORM;

use IC\Bundle\Base\ComponentBundle\DataFixtures\AbstractFixtureCreate;
use IC\Bundle\Base\RestBundle\Entity;

/**
 * Fixture
 *
 * @author Juti Noppornpitak <jutin@nationalfibre.net>
 * @author John Cartwright <johnc@nationalfibre.net>
 */
class Foo extends AbstractFixtureCreate
{
    /**
     * {@inheritdoc}
     */
    protected function buildEntity($data)
    {
        $entity = new Entity\Foo();

        $entity->setContent($data['content']);

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataList()
    {
        return array(
            'ic_base_rest.foo#1' => array(
                'content' => 'symfony'
            ),
            'ic_base_rest.foo#2' => array(
                'content' => 'doctrine'
            ),
        );
    }
}
