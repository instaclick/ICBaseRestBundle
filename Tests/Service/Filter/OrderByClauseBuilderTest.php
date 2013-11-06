<?php
/**
 * @copyright 2013 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Tests\Service\Filter;

use IC\Bundle\Base\TestBundle\Test\TestCase;
use IC\Bundle\Base\RestBundle\Service\Filter\OrderByClauseBuilder;

/**
 * Test the order-by clause builder
 *
 * @group Unit
 * @group Service
 * @group Filter
 *
 * @author Juti Noppornpitak <jutin@nationalfibre.net>
 */
class OrderByClauseBuilderTest extends TestCase
{
    /**
     * @var \IC\Bundle\Base\RestBundle\Service\Filter\OrderByClauseBuilder
     */
    private $clauseBuilder;

    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadataInfo
     */
    private $classMetadata;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->classMetadata = $this->createMock('Doctrine\ORM\Mapping\ClassMetadataInfo');
        $this->clauseBuilder = new OrderByClauseBuilder($this->classMetadata);
    }

    /**
     * Test build for a primitive property in the base case.
     */
    public function testBuildForPrimitivePropertyOnBaseCase()
    {
        $propertyName  = 'name';
        $propertyOrder = 'asc';

        $this->classMetadata
            ->expects($this->once())
            ->method('hasField')
            ->with($propertyName)
            ->will($this->returnValue(true));

        $orderBy = $this->clauseBuilder->build(array($propertyName => $propertyOrder));

        $this->assertEquals(1, $orderBy->count());
    }

    /**
     * Test build for a primitive property in the general case.
     */
    public function testBuildForPrimitivePropertyOnGeneralCase()
    {
        $propertyMap = array(
            'registeredDate' => 'desc',
            'name'           => 'asc',
            'address'        => 'asc',
            'numberOfPanda'  => null,
        );

        $expectedPartList = array(
            'e.registeredDate desc',
            'e.name asc',
            'e.address asc',
            'e.numberOfPanda ASC',
        );

        $this->classMetadata
            ->expects($this->any())
            ->method('hasField')
            ->with($this->anyThing())
            ->will($this->returnValue(true));

        $orderBy = $this->clauseBuilder->build($propertyMap);

        $this->assertEquals(4, $orderBy->count());
        $this->assertEquals($expectedPartList, $orderBy->getParts());
    }

    /**
     * Test build for a singular associative property on the base case.
     */
    public function testBuildForSingularAssociativePropertyOnBaseCase()
    {
        $propertyName  = 'name';
        $propertyOrder = 'asc';

        $this->classMetadata
            ->expects($this->once())
            ->method('hasField')
            ->with($propertyName)
            ->will($this->returnValue(false));

        $this->classMetadata
            ->expects($this->once())
            ->method('hasAssociation')
            ->with($propertyName)
            ->will($this->returnValue(true));

        $this->classMetadata
            ->expects($this->once())
            ->method('isSingleValuedAssociation')
            ->with($propertyName)
            ->will($this->returnValue(true));

        $orderBy = $this->clauseBuilder->build(array($propertyName => $propertyOrder));

        $this->assertEquals(1, $orderBy->count());
    }

    /**
     * Test build for a singular associative property in the general case.
     */
    public function testBuildForSingularAssociativePropertyOnGeneralCase()
    {
        $propertyMap = array(
            'registeredDate' => 'desc',
            'name'           => 'asc',
            'address'        => 'awesome order', // should be overwritten as "ASC"
            'numberOfPanda'  => null,
        );

        $expectedPartList = array(
            'e.registeredDate desc',
            'e.name asc',
            'e.address ASC',
            'e.numberOfPanda ASC',
        );

        $this->classMetadata
            ->expects($this->any())
            ->method('hasField')
            ->with($this->anyThing())
            ->will($this->returnValue(false));

        $this->classMetadata
            ->expects($this->any())
            ->method('hasAssociation')
            ->with($this->anyThing())
            ->will($this->returnValue(true));

        $this->classMetadata
            ->expects($this->any())
            ->method('isSingleValuedAssociation')
            ->with($this->anyThing())
            ->will($this->returnValue(true));

        $orderBy = $this->clauseBuilder->build($propertyMap);

        $this->assertEquals(4, $orderBy->count());
        $this->assertEquals($expectedPartList, $orderBy->getParts());
    }

    /**
     * Test build for a invalid property on the general case.
     */
    public function testBuildForInvalidPropertiesInGeneralCase()
    {
        $propertyMap = array(
            'registeredDate' => 'desc',
            'name'           => 'asc',
            'address'        => 'asc',
            'numberOfPanda'  => null,
        );

        $expectedPartList = array();

        $this->classMetadata
            ->expects($this->any())
            ->method('hasField')
            ->with($this->anyThing())
            ->will($this->returnValue(false));

        $this->classMetadata
            ->expects($this->any())
            ->method('hasAssociation')
            ->with($this->anyThing())
            ->will($this->returnValue(false));

        $this->classMetadata
            ->expects($this->any())
            ->method('isSingleValuedAssociation')
            ->with($this->anyThing())
            ->will($this->returnValue(false));

        $orderBy = $this->clauseBuilder->build($propertyMap);

        $this->assertEquals(0, $orderBy->count());
        $this->assertEquals($expectedPartList, $orderBy->getParts());
    }
}
