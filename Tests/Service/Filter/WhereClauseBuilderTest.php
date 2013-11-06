<?php
/**
 * @copyright 2013 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Tests\Service\Filter;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Func;
use IC\Bundle\Base\RestBundle\Service\Filter\WhereClauseBuilder;
use IC\Bundle\Base\TestBundle\Test\TestCase;

/**
 * Query Where Clause Builder Test
 *
 * @group Filter
 * @group Service
 * @group Unit
 *
 * @author Mark Kasaboski <markk@nationalfibre.net>
 */
class WhereClauseBuilderTest extends TestCase
{
    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    private $classMetadataInfo;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->classMetadataInfo = $this->createMock('Doctrine\ORM\Mapping\ClassMetadata');
    }

    /**
     * Test WhereClauseBuilder build
     *
     * @param array   $propertyMap    The properties to build a where clause with
     * @param integer $expectedCount  The expected where clause count
     * @param string  $expectedResult The expected result
     *
     * @dataProvider provideDataForTestWhereClauseBuild
     */
    public function testWhereClauseBuild($propertyMap, $expectedCount, $expectedResult)
    {
        $this
            ->classMetadataInfo
            ->expects($this->any())
            ->method('hasField')
            ->with($this->isType('string'))
            ->will($this->returnValue(true));

        $whereClauseBuilder = new WhereClauseBuilder($this->classMetadataInfo);

        $this->expectOutputString($expectedResult);

        $whereClause = $whereClauseBuilder->build($propertyMap);

        $this->assertEquals($expectedCount, $whereClause->count());
        $this->assertEquals($expectedResult, $whereClause->getParts());
    }

    /**
     * Data provider for testWhereClauseBuild
     *
     * @return array
     */
    public function provideDataForTestWhereClauseBuild()
    {
        return array(
            array(
                array(
                    'property_name' => array(),
                ),
                1,
                array(
                    new Func('e.propertyName IN', array(
                            ':property_name',
                    )),
                ),
            ),
            array(
                array(
                    'property_name' => 'NULL',
                ),
                1,
                array(
                    'e.propertyName IS NULL',
                ),
            ),
            array(
                array(
                    'property_name' => '*',
                ),
                1,
                array(
                    new Comparison('e.propertyName', 'LIKE', ':property_name'),
                ),
            ),
            array(
                array(
                    'property_name1' => 'property_value1',
                    'property_name2' => 'property_value2',
                ),
                2,
                array(
                    new Comparison('e.propertyName1', '=', ':property_name1'),
                    new Comparison('e.propertyName2', '=', ':property_name2'),
                ),
            ),
            array(
                array(),
                0,
                array(),
            ),
        );
    }
}
