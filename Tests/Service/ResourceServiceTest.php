<?php
/**
 * @copyright 2013 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Tests\Service;

use Symfony\Component\HttpFoundation\Request;
use IC\Bundle\Base\ComponentBundle\Exception\ServiceException;
use IC\Bundle\Base\RestBundle\Service\ResourceService;
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
class ResourceServiceTest extends TestCase
{
    /**
     * @var \IC\Bundle\Base\RestBundle\Service\ResourceService
     */
    private $resourceService;

    /**
     * @var \IC\Bundle\Base\ComponentBundle\Entity\Entity
     */
    private $baseEntity;

    /**
     * {@inherit}
     */
    public function setUp()
    {
        parent::setUp();

        $this->resourceService = new ResourceService;

        $this->baseEntity = $this->createMock('IC\Bundle\Base\ComponentBundle\Entity\Entity');

        $this->resourceService->setEntityRepository($this->createEntityRepositoryMock());
        $this->resourceService->setAllowedMethodList(array('get', 'post', 'put', 'delete'));
    }

    /**
     * Should do a post request
     */
    public function testShouldPostRequest()
    {
        $this->resourceService->setMediatorService($this->createMediatorServiceMock());

        $entityResponse = $this->resourceService->post(new Request);
        $arrayResponse  = $this->resourceService->post(new Request);

        $this->assertInstanceOf('IC\Bundle\Base\ComponentBundle\Entity\Entity', $entityResponse);
        $this->assertInstanceOf('IC\Bundle\Base\ComponentBundle\Entity\Entity', array_pop($arrayResponse));
    }

    /**
     * Should do a get request
     */
    public function testShouldGetRequest()
    {
        $entityResponse = $this->resourceService->get($this->createRequestMock());

        $this->assertInstanceOf('IC\Bundle\Base\ComponentBundle\Entity\Entity', $entityResponse);
    }

    /**
     * Should do a put request
     */
    public function testShouldPutRequest()
    {
        $this->resourceService->setMediatorService($this->createMediatorServiceMock());

        $entityResponse = $this->resourceService->put(new Request);
        $arrayResponse  = $this->resourceService->put(new Request);

        $this->assertInstanceOf('IC\Bundle\Base\ComponentBundle\Entity\Entity', $entityResponse);
        $this->assertInstanceOf('IC\Bundle\Base\ComponentBundle\Entity\Entity', array_pop($arrayResponse));
    }

    /**
     * Should do a delete request
     */
    public function testShouldDeleteRequest()
    {
        $entityRepositoryMock = $this->createEntityRepositoryMock();

        $entityRepositoryMock
            ->expects($this->any())
            ->method('delete')
            ->will($this->returnValue(true));

        $this->resourceService->setEntityRepository($entityRepositoryMock);

        $response = $this->resourceService->delete($this->createRequestMock());

        $this->assertTrue($response);
    }

    /**
     * Should throw and IC\Bundle\Base\ComponentBundle\Exception\ServiceException to a delete request
     */
    public function testShouldThrowExceptionForDeleteRequest()
    {
        $entityRepositoryMock = $this->createEntityRepositoryMock();

        $entityRepositoryMock
            ->expects($this->any())
            ->method('delete')
            ->will($this->throwException(new ServiceException));

        $this->resourceService->setEntityRepository($entityRepositoryMock);

        $response = $this->resourceService->delete($this->createRequestMock(2));

        $this->assertFalse($response);
    }

    /**
     * Should get the package name
     */
    public function testShouldGetPackageName()
    {
        $this->assertEquals($this->resourceService->getPackageName(), 'entityRepositoryMockPackage');
    }

    /**
     * Should get the sub package name
     */
    public function testShouldGetSubPackageName()
    {
        $this->assertEquals($this->resourceService->getSubPackageName(), 'entityRepositoryMockSubPackage');
    }

    /**
     * Should get the entity name
     */
    public function testShouldGetEntityName()
    {
        $this->assertEquals($this->resourceService->getEntityName(), 'stdClass');
    }

    /**
     * Should get allowed method list
     */
    public function testShouldGetAllowedMethodList()
    {
        $this->assertEquals($this->resourceService->getAllowedMethodList(), array('get', 'post', 'put', 'delete'));
        $this->assertCount(4, $this->resourceService->getAllowedMethodList());
    }

    /**
     * Should get entity repository
     */
    public function testShouldGetEntityRepository()
    {
        $this->assertEquals($this->resourceService->getEntityRepository(), $this->createEntityRepositoryMock());
        $this->assertInstanceOf('IC\Bundle\Base\ComponentBundle\Entity\Repository\EntityRepository', $this->resourceService->getEntityRepository());
    }

    /**
     * Test for classname retrival.
     */
    public function testGetClassName()
    {
        $entityRepositoryMock = $this->createMock('IC\Bundle\Base\ComponentBundle\Entity\Repository\EntityRepository');
        $entityRepositoryMock
            ->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue('Panda'));

        $this->resourceService->setEntityRepository($entityRepositoryMock);

        $this->assertEquals('Panda', $this->resourceService->getClassName());
    }

    /**
     * Test for mediator service retrival
     */
    public function testGetMediatorService()
    {
        $service = $this->createMock('IC\Bundle\Base\RestBundle\Service\MediatorService');

        $this->resourceService->setMediatorService($service);

        $this->assertEquals($service, $this->resourceService->getMediatorService());
    }

    /**
     * Mock an Entity Repository
     *
     * @return \IC\Bundle\Base\ComponentBundle\Entity\Repository\EntityRepository
     */
    private function createEntityRepositoryMock()
    {
        $entityRepositoryMock = $this->createMock('IC\Bundle\Base\ComponentBundle\Entity\Repository\EntityRepository');

        $entityRepositoryMock
            ->expects($this->any())
            ->method('getPackageName')
            ->will($this->returnValue('entityRepositoryMockPackage'));

        $entityRepositoryMock
            ->expects($this->any())
            ->method('getSubPackageName')
            ->will($this->returnValue('entityRepositoryMockSubPackage'));

        $entityRepositoryMock
            ->expects($this->any())
            ->method('getEntityName')
            ->will($this->returnValue('stdClass'));

        $entityRepositoryMock
            ->expects($this->any())
            ->method('post')
            ->will($this->returnValue(true));

        $entityRepositoryMock
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValue($this->baseEntity));

        $entityRepositoryMock
            ->expects($this->any())
            ->method('put')
            ->will($this->returnValue(true));

        return $entityRepositoryMock;
    }

    /**
     * Mock a Mediator Service
     *
     * @return \IC\Bundle\Base\RestBundle\Service\MediatorService
     */
    private function createMediatorServiceMock()
    {
        $mediatorServiceMock = $this->createMock('IC\Bundle\Base\RestBundle\Service\MediatorService');

        $mediatorServiceMock
            ->expects($this->at(0))
            ->method('mediate')
            ->will($this->returnValue($this->baseEntity));

        $mediatorServiceMock
            ->expects($this->at(1))
            ->method('mediate')
            ->will($this->returnValue(array($this->baseEntity)));

        return $mediatorServiceMock;
    }

    /**
     * Mock a Request
     *
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    private function createRequestMock($id = 1)
    {
        $requestMock = $this->createMock('Symfony\Component\HttpFoundation\Request');

        $requestMock
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue($id));

        return $requestMock;
    }
}
