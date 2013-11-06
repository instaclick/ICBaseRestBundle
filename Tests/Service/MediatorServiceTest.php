<?php
/**
 * @copyright 2013 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Tests\Service;

use IC\Bundle\Base\RestBundle\Service\MediatorService;
use IC\Bundle\Base\TestBundle\Test\TestCase;

/**
 * Test the entity getters and setters
 *
 * @group ICBaseRestBundle
 * @group Service
 * @group Unit
 *
 * @author Kinn Coelho Julião <kinnj@nationalfibre.net>
 * @author Mark Kasaboski <markk@nationalfibre.net>
 */
class MediatorServiceTest extends TestCase
{
    /**
     * @var \IC\Bundle\Base\RestBundle\Service\MediatorService
     */
    private $mediatorService;

    /**
     * @var \JMS\Serializer\SerializerInterface
     */
    private $serializerServiceMock;

    /**
     * @var \IC\Bundle\Base\RestBundle\Entity\Entity
     */
    private $baseEntity;

    /**
     * {@inherit}
     */
    public function setUp()
    {
        parent::setUp();

        $this->mediatorService       = new MediatorService;
        $this->serializerServiceMock = $this->createMock('JMS\Serializer\SerializerInterface');
        $this->baseEntity            = $this->createMock('IC\Bundle\Base\ComponentBundle\Entity\Entity');
    }

    /**
     * Should mediate the request
     */
    public function testShouldMediateRequest()
    {
        $this
            ->serializerServiceMock
            ->expects($this->once())
            ->method('deserialize')
            ->with(json_encode(1), 'stdClass', 'application/json')
            ->will($this->returnValue($this->baseEntity));

        $this->mediatorService->setSerializerService($this->serializerServiceMock);
        $this->mediatorService->setValidatorService($this->createValidatorServiceMock(false));
        $this->mediatorService->setFilterService($this->createFilterServiceMock());

        $response = $this->mediatorService->mediate($this->createRequestMock(), $this->createEntityRepositoryMock());

        $this->assertEquals($this->baseEntity, $response);
    }

    /**
     * Should return 400 for the request
     */
    public function testShouldReturn404ForMediateRequest()
    {
        $this
            ->serializerServiceMock
            ->expects($this->once())
            ->method('deserialize')
            ->with(json_encode(1), 'stdClass', 'application/json')
            ->will($this->returnValue($this->baseEntity));

        $this->mediatorService->setSerializerService($this->serializerServiceMock);
        $this->mediatorService->setValidatorService($this->createValidatorServiceMock(true));
        $this->mediatorService->setFilterService($this->createFilterServiceMock());

        $response = $this->mediatorService->mediate($this->createRequestMock(), $this->createEntityRepositoryMock());

        $this->assertCount(3, $response);
        $this->assertContains(400, $response);
        $this->assertContains('Entity is not valid.', $response);
    }

    /**
     * Should return the previous exception for the request
     */
    public function testShouldReturnThePreviousExceptionForMediateRequest()
    {
        $this
            ->serializerServiceMock
            ->expects($this->once())
            ->method('deserialize')
            ->with(json_encode(1), 'stdClass', 'application/json')
            ->will($this->throwException(
                new \Exception('Exception', 0, new \Exception('Previous Exception'))
            ));

        $this->mediatorService->setFilterService($this->createFilterServiceMock('never'));
        $this->mediatorService->setSerializerService($this->serializerServiceMock);
        $this->mediatorService->setValidatorService($this->createValidatorServiceMock(true, 'never'));

        $response = $this->mediatorService->mediate($this->createRequestMock(), $this->createEntityRepositoryMock());

        $this->assertCount(3, $response);
        $this->assertContains(400, $response);
        $this->assertContains('Unable to build entity.', $response);
    }

    /**
     * Mock a Filter Service
     *
     * @param string $filterEntityCallCount filterEntity call count
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createFilterServiceMock($filterEntityCallCount = 'once')
    {
        $filterServiceMock = $this->createMock('DMS\Bundle\FilterBundle\Service\Filter');

        $filterServiceMock
            ->expects($this->$filterEntityCallCount())
            ->method('filterEntity');

        return $filterServiceMock;
    }

    /**
     * Mock a Validator Service
     *
     * @param boolean $returnValue     Expected return value
     * @param string  $methodCallCount Method call count
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createValidatorServiceMock($returnValue = true, $methodCallCount = 'once')
    {
        $validationVisitorMock = $this->createMock('Symfony\Component\Validator\ConstraintViolationList');

        $validationVisitorMock
            ->expects($this->$methodCallCount())
            ->method('count')
            ->will($this->returnValue($returnValue));

        $validatorServiceMock = $this->createMock('Symfony\Component\Validator\Validator');

        $validatorServiceMock
            ->expects($this->$methodCallCount())
            ->method('validate')
            ->will($this->returnValue($validationVisitorMock));

        return $validatorServiceMock;
    }

    /**
     * Mock an Entity Repository
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createEntityRepositoryMock()
    {
        $entityRepositoryMock = $this->createMock('IC\Bundle\Base\ComponentBundle\Entity\Repository\EntityRepository');

        $entityRepositoryMock
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue('stdClass'));

        return $entityRepositoryMock;
    }

    /**
     * Mock a Request
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createRequestMock()
    {
        $requestMock = $this->createMock('Symfony\Component\HttpFoundation\Request');

        $requestMock
            ->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue(json_encode(1)));

        $requestMock
            ->expects($this->once())
            ->method('getRequestFormat')
            ->will($this->returnValue('application/json'));

        return $requestMock;
    }
}
