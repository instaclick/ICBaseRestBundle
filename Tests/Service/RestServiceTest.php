<?php
/**
 * @copyright 2013 InstaClick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Tests\Service;

use IC\Bundle\Base\RestBundle\Entity\Foo;
use IC\Bundle\Base\RestBundle\Service\ResourceService;
use IC\Bundle\Base\RestBundle\Service\ResourceTranscoderService;
use IC\Bundle\Base\RestBundle\Service\RestService;
use IC\Bundle\Base\SecurityBundle\Service\AuthorizationService;
use IC\Bundle\Base\TestBundle\Test\TestCase;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Test cases for the RestService class
 *
 * @group Unit
 * @group Service
 *
 * @author Ryan Albon <ralbon@nationalfibre.net>
 * @author David Maignan <davidm@nationalfibre.net>
 */
class RestServiceTest extends TestCase
{
    /**
     * Test the success path through the filter method
     *
     * @param \Symfony\Component\HttpFoundation\Request     $request           The HTTP request to pass into the SUT
     * @param array                                         $allowedMethodList The HTTP methods permitted by the resource service
     * @param \IC\Bundle\Base\ComponentBundle\Entity\Entity $entity            The entity to be returned by the resource service
     * @param string                                        $content           The content to be returned by the serializer service
     *
     * @dataProvider provideDataForTestFilterSuccessPath
     */
    public function testFilterSuccessPath(Request $request, array $allowedMethodList, $entity, $content)
    {
        $resourceService           = $this->createResourceServiceMock($allowedMethodList, array('filter' => $entity));
        $resourceTranscoderService = $this->createResourceTranscoderServiceMock($request->getPathInfo(), $resourceService);
        $serializerService         = $this->createSerializerServiceMock($entity, $content);
        $authorizationService      = $this->createAuthorizationServiceMock('VIEW', $entity);
        $restService               = $this->createRestServiceMock($serializerService, $resourceTranscoderService, $authorizationService);

        $response = $restService->filter($request);

        $this->assertHttpStatusAndContent($response, 200, $content);
    }

    /**
     * Provide data sets to the testFilterSuccessPath method
     *
     * @return array
     */
    public function provideDataForTestFilterSuccessPath()
    {
        return array(
            array(Request::create('/', 'GET'), array('GET'), new Foo(), 'some content'),
            array(Request::create('/', 'GET'), array('DELETE', 'GET', 'POST', 'PUT'), new Foo(), 'some content'),
        );
    }

    /**
     * Test the error paths through the filter method
     *
     * @param \Symfony\Component\HttpFoundation\Request $request           The HTTP request to pass into the SUT
     * @param array                                     $allowedMethodList The HTTP methods permitted by the resource service
     *
     * @dataProvider provideDataForTestFilterErrorPath
     */
    public function testFilterErrorPath(Request $request, array $allowedMethodList)
    {
        $resourceService           = $this->createResourceServiceMock($allowedMethodList, array());
        $resourceTranscoderService = $this->createResourceTranscoderServiceMock($request->getPathInfo(), $resourceService);
        $serializerService         = $this->createSerializerServiceMock();
        $authorizationService      = $this->createAuthorizationServiceMock();
        $restService               = $this->createRestServiceMock($serializerService, $resourceTranscoderService, $authorizationService);

        $response = $restService->filter($request);

        $this->assertEquals(405, $response->getStatusCode());
    }

    /**
     * Provide data sets for the testFilterErrorPath method
     *
     * @return array
     */
    public function provideDataForTestFilterErrorPath()
    {
        return array(
            // The SUT should return an HTTP 405 because GET isn't permitted by the resource service
            array(Request::create('/', 'GET'), array()),
            array(Request::create('/', 'GET'), array('DELETE', 'POST', 'PUT')),
        );
    }

    /**
     * Test filter failure for non authorized class
     */
    public function testFilterFailsClassNotAuthorized()
    {
        $request                   = Request::create('/', 'GET');
        $allowedMethodList         = array('GET');
        $entity                    = $this->getHelper('Unit\Entity')->createMock('IC\Bundle\Base\RestBundle\Entity\Foo', 1);
        $authorizationService      = $this->createMock('IC\Bundle\Base\SecurityBundle\Service\AuthorizationService');
        $resourceService           = $this->createResourceServiceMock($allowedMethodList, array('filter' => $entity));
        $resourceTranscoderService = $this->createResourceTranscoderServiceMock($request->getPathInfo(), $resourceService);
        $serializerService         = $this->createSerializerServiceMock($entity, '{mock content}');
        $restService               = $this->createRestServiceMock($serializerService, $resourceTranscoderService, $authorizationService);

        $authorizationService->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(false));

        $restService->setAuthorizationService($authorizationService);

        $response = $restService->filter($request);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Authorization required.', $response->getContent());
    }

    /**
     * Test the success path through the post method
     *
     * @param \Symfony\Component\HttpFoundation\Request     $request           The HTTP request to pass into the SUT
     * @param array                                         $allowedMethodList The HTTP methods permitted by the resource service
     * @param \IC\Bundle\Base\ComponentBundle\Entity\Entity $entity            The entity to be returned by the resource service
     * @param string                                        $content           The content to be returned by the serializer
     *
     * @dataProvider provideDataForTestPostSuccessPath
     */
    public function testPostSuccessPath(Request $request, array $allowedMethodList, $entity, $content)
    {
        $resourceService           = $this->createResourceServiceMock($allowedMethodList, array('post' => $entity));
        $resourceTranscoderService = $this->createResourceTranscoderServiceMock($request->getPathInfo(), $resourceService);
        $serializerService         = $this->createSerializerServiceMock($entity, $content);
        $authorizationService      = $this->createAuthorizationServiceMock('CREATE', $entity);
        $restService               = $this->createRestServiceMock($serializerService, $resourceTranscoderService, $authorizationService);

        $response = $restService->post($request);

        $this->assertHttpStatusAndContent($response, 201, $content);
    }

    /**
     * Provide data sets to the testPostSuccessPath method
     *
     * @return array
     */
    public function provideDataForTestPostSuccessPath()
    {
        return array(
            array(Request::create('/', 'POST'), array('POST'), new Foo(), 'some content'),
            array(Request::create('/', 'POST'), array('DELETE', 'GET', 'POST', 'PUT'), new Foo(), 'some content'),
        );
    }

    /**
     * Test post failure for not allowed http method
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param array                                     $allowedMethodList
     * @param integer                                   $expectedStatusCode
     *
     * @dataProvider dataProviderPostErrorMethodNotAllowed
     */
    public function testPostErrorMethodsNotAllowed($request, $allowedMethodList, $expectedStatusCode)
    {
        $entity                    = $this->getHelper('Unit\Entity')->createMock('IC\Bundle\Base\RestBundle\Entity\Foo', 1);
        $resourceService           = $this->createResourceServiceMock($allowedMethodList, array('post' => $entity));
        $resourceTranscoderService = $this->createResourceTranscoderServiceMock($request->getPathInfo(), $resourceService);
        $authorizationService      = $this->createMock('IC\Bundle\Base\SecurityBundle\Service\AuthorizationService');
        $serializerService         = $this->createSerializerServiceMock();
        $restService               = $this->createRestServiceMock($serializerService, $resourceTranscoderService, $authorizationService);

        $response = $restService->post($request);

        $this->assertEquals($expectedStatusCode, $response->getStatusCode());
        $this->assertEquals('HTTP method not allowed.', $response->getContent());
    }

    /**
     * Data provider for post error method not allowed
     *
     * @return array
     */
    public function dataProviderPostErrorMethodNotAllowed()
    {
        $data = array();

        $data[] = array(
            'request'            => Request::create('/', 'POST'),
            'allowedMethodList'  => array(),
            'expectedStatusCode' => 405
        );

        $data[] = array(
            'request'            => Request::create('/', 'POST'),
            'allowedMethodList'  => array('DELETE', 'GET', 'PUT'),
            'expectedStatusCode' => 405
        );

        return $data;
    }

    /**
     * Test post error for class non authorized
     */
    public function testPostErrorClassAccessNonAuthorized()
    {
        $request                   = Request::create('/', 'POST');
        $allowedMethodList         = array('POST');
        $entity                    = $this->getHelper('Unit\Entity')->createMock('IC\Bundle\Base\RestBundle\Entity\Foo', 1);
        $resourceService           = $this->createResourceServiceMock($allowedMethodList, array('post' => $entity));
        $resourceTranscoderService = $this->createResourceTranscoderServiceMock($request->getPathInfo(), $resourceService);
        $authorizationService      = $this->createMock('IC\Bundle\Base\SecurityBundle\Service\AuthorizationService');
        $serializerService         = $this->createSerializerServiceMock();
        $restService               = $this->createRestServiceMock($serializerService, $resourceTranscoderService, $authorizationService);

        $authorizationService->expects($this->once())
            ->method('isGranted')
            ->with('CREATE', $entity)
            ->will($this->returnValue(false));

        $restService->setAuthorizationService($authorizationService);

        $response = $restService->post($request);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Authorization required.', $response->getContent());
    }

    /**
     * Provide data sets to the testPostErrorPath method
     *
     * @return array
     */
    public function provideDataForTestPostErrorPath()
    {
        return array(
            // The SUT should return an HTTP 405 because POST isn't one of the allowed methods
            array(Request::create('/', 'POST'), array(), null, 405),
            array(Request::create('/', 'POST'), array('DELETE', 'GET', 'PUT'), null, 405, array(null, null)),

            // The SUT should return an HTTP 400 because the resource service returns
            // something that isn't an entity
            array(Request::create('/', 'POST'), array('POST'), null, 400, array('CREATE', null)),
            array(Request::create('/', 'POST'), array('DELETE', 'GET', 'POST', 'PUT'), null, 400, array('CREATE', null)),
            array(Request::create('/', 'POST'), array('DELETE', 'GET', 'POST', 'PUT'), 'mockClassName', 400, array('CREATE', 'mockClassName')),
        );
    }

    /**
     * Test the success paths through the get method
     *
     * @param \Symfony\Component\HttpFoundation\Request     $request           The HTTP request to pass into the SUT
     * @param array                                         $allowedMethodList The HTTP methods permitted by the resource service
     * @param \IC\Bundle\Base\ComponentBundle\Entity\Entity $entity            The entity to be returned by the resource service
     * @param string                                        $content           The content to be returned by the serializer
     *
     * @dataProvider provideDataForTestGetSuccessPath
     */
    public function testGetSuccessPath(Request $request, array $allowedMethodList, $entity, $content)
    {
        $resourceService           = $this->createResourceServiceMock($allowedMethodList, array('get' => $entity), $request);
        $resourceTranscoderService = $this->createResourceTranscoderServiceMock($request->getPathInfo(), $resourceService);
        $serializerService         = $this->createSerializerServiceMock($entity, $content);
        $authorizationService      = $this->createAuthorizationServiceMock('VIEW', $entity);
        $restService               = $this->createRestServiceMock($serializerService, $resourceTranscoderService, $authorizationService);

        $response = $restService->get($request);

        $this->assertHttpStatusAndContent($response, 200, $content);
    }

    /**
     * Provide data inputs to the testGetSuccessPath method
     *
     * @return array
     */
    public function provideDataForTestGetSuccessPath()
    {
        return array(
            array(Request::create('/', 'GET'), array('GET'), new Foo(), 'some content'),
            array(Request::create('/', 'GET'), array('DELETE', 'GET', 'POST', 'PUT'), new Foo(), 'some content'),
        );
    }

    /**
     * Test get error paths
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param array                                     $allowedMethodList
     * @param mixed                                     $entity
     * @param integer                                   $expectedStatusCode
     *
     * @dataProvider provideDataForTestGetErrorPaths
     */
    public function testGetErrorPaths(Request $request, array $allowedMethodList, $entity, $expectedStatusCode)
    {
        $resourceService           = $this->createResourceServiceMock($allowedMethodList, array('get' => $entity));
        $resourceTranscoderService = $this->createResourceTranscoderServiceMock($request->getPathInfo(), $resourceService);
        $serializerService         = $this->createSerializerServiceMock();
        $authorizationService      = $this->createAuthorizationServiceMock();
        $restService               = $this->createRestServiceMock($serializerService, $resourceTranscoderService, $authorizationService);

        $response = $restService->get($request);

        $this->assertEquals($expectedStatusCode, $response->getStatusCode());
    }

    /**
     * Provide inputs to the testGetErrorPaths method
     *
     * @return array
     */
    public function provideDataForTestGetErrorPaths()
    {
        return array(
            // The SUT should return an HTTP 405 because the GET method is not permitted
            array(Request::create('/', 'GET'), array(), new Foo(), 405),
            array(Request::create('/', 'GET'), array('POST', 'PUT', 'DELETE'), new Foo(), 405),

            // The SUT should return an HTTP 404 because the resource service returns
            // something that isn't an entity
            array(Request::create('/', 'GET'), array('GET'), null, 404),
            array(Request::create('/', 'GET'), array('GET'), false, 404),
        );
    }

    /**
     * Test the success path through the put method
     *
     * @param \Symfony\Component\HttpFoundation\Request     $request           The HTTP request to pass into the SUT
     * @param array                                         $allowedMethodList The HTTP methods permitted by the resource service
     * @param \IC\Bundle\Base\ComponentBundle\Entity\Entity $entity            The entity to be returned by the resource service
     * @param string                                        $content           The content to be returned by the serializer
     * @param integer                                       $statusCode        The http status code to be returned by the serializer
     *
     * @dataProvider provideDataForTestPutSuccessPath
     */
    public function testPutSuccessPath(Request $request, array $allowedMethodList, $entity, $content, $statusCode)
    {
        $resourceService           = $this->createResourceServiceMock($allowedMethodList, array('put' => $entity));
        $resourceTranscoderService = $this->createResourceTranscoderServiceMock($request->getPathInfo(), $resourceService);
        $serializerService         = $this->createSerializerServiceMock($entity, $content);
        $authorizationService      = $this->createAuthorizationServiceMock('EDIT', $entity);
        $mediatorService           = $this->createMediatorServiceMock($request, $resourceService, $entity);
        $restService               = $this->createRestServiceMock($serializerService, $resourceTranscoderService, $authorizationService);

        $response = $restService->put($request);

        $this->assertHttpStatusAndContent($response, $statusCode, $content);
    }

    /**
     * Provide inputs to the testPutSuccessPath method
     *
     * @return array
     */
    public function provideDataForTestPutSuccessPath()
    {
        return array(
            array(Request::create('/', 'PUT'), array('PUT'), new Foo(), 'some content', 200),
            array(Request::create('/', 'PUT'), array('DELETE', 'GET', 'POST', 'PUT'), new Foo(), 'some content', 200),
            array(Request::create('/', 'PUT'), array('DELETE', 'GET', 'POST', 'PUT'), null, 'Resource not found.', 404),
        );
    }

    /**
     * Test the error paths through the put method
     *
     * @param \Symfony\Component\HttpFoundation\Request $request            The HTTP request to pass into the SUT
     * @param array                                     $allowedMethodList  The HTTP methods permitted by the resource service
     * @param mixed                                     $entity             The entity to be returned by the resource service
     * @param integer                                   $expectedStatusCode The expected status code on the resulting HTTP response
     * @param array                                     $authorizationConfig
     *
     * @dataProvider provideDataForTestPutErrorPaths
     */
    public function testPutErrorPaths(Request $request, array $allowedMethodList, $entity, $expectedStatusCode, $authorizationConfig)
    {
        $resourceService           = $this->createResourceServiceMock($allowedMethodList, array('put' => $entity));
        $resourceTranscoderService = $this->createResourceTranscoderServiceMock($request->getPathInfo(), $resourceService);
        $serializerService         = $this->createSerializerServiceMock($entity);
        $mediatorService           = $this->createMediatorServiceMock($request, $resourceService, $entity);
        $authorizationService      = $this->createAuthorizationServiceMock($authorizationConfig[0], $authorizationConfig[1]);
        $restService               = $this->createRestServiceMock($serializerService, $resourceTranscoderService, $authorizationService);

        $response = $restService->put($request);

        $this->assertEquals($expectedStatusCode, $response->getStatusCode());
    }

    /**
     * Provide inputs to the testPutErrorPaths method
     *
     * @return array
     */
    public function provideDataForTestPutErrorPaths()
    {
        return array(
            // The SUT should return an HTTP 405 because PUT is not allowed
            array(Request::create('/', 'PUT'), array(), null, 405, array(null, null)),
            array(Request::create('/', 'PUT'), array('POST', 'DELETE', 'GET'), null, 405, array(null, null)),
            array(Request::create('/', 'PUT'), array('POST', 'DELETE', 'GET'), new Foo(), 405, array(null, null)),
        );
    }

    /**
     * Test put fails from mediator service not returning an entity
     *
     * @param array  $mediatorServiceResponse Response from the mediator service
     * @param string $expected                Expected message
     *
     * @dataProvider provideDataForMediatorServiceFailsReturnEntity
     */
    public function testPutErrorMediatorServiceFailsReturnEntity($mediatorServiceResponse, $expected)
    {
        $request                   = Request::create('/', 'PUT');
        $allowedMethodList         = array('PUT');
        $authorizationService      = $this->createMock('IC\Bundle\Base\SecurityBundle\Service\AuthorizationService');
        $entity                    = $this->getHelper('Unit\Entity')->createMock('IC\Bundle\Base\RestBundle\Entity\Foo', 1);
        $resourceService           = $this->createResourceServiceMock($allowedMethodList, array('put' => $entity));
        $resourceTranscoderService = $this->createResourceTranscoderServiceMock($request->getPathInfo(), $resourceService);
        $mediatorService           = $this->createMediatorServiceMock($request, $resourceService, $mediatorServiceResponse);
        $serializerService         = $this->createSerializerServiceMock($entity, '{mock content}');
        $restService               = $this->createRestServiceMock($serializerService, $resourceTranscoderService, $authorizationService);

        $response = $restService->put($request);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals($expected, $response->getContent());
    }

    /**
     * Data provider for Mediator service fails for put method
     *
     * @return array
     */
    public function provideDataForMediatorServiceFailsReturnEntity()
    {
        $data = array();

        $validatorConstraint = new ConstraintViolation('mock validator constraint message', '', array(), null, '', null, null);

        $data[] = array(
            array(
                'message'   => 'Entity is not valid.',
                'code'      => 400,
                'validator' => new ArrayCollection(array($validatorConstraint))
            ),
            '["mock validator constraint message"]'
        );

        $data[] = array(
            array(
                'message'  => 'Unable to build entity.',
                'code'     => 400,
                'previous' => array(
                    'message'  => 'mock exception message',
                    'code'     => 'mock exception code'
                )
            ),
            '["mock exception message"]'
        );

        return $data;
    }

    /**
     * Test put fails for non authorized object
     */
    public function testPutFailsForNonAuthorizedObject()
    {
        $request                   = Request::create('/', 'PUT');
        $allowedMethodList         = array('PUT');
        $authorizationService      = $this->createMock('IC\Bundle\Base\SecurityBundle\Service\AuthorizationService');
        $entity                    = $this->getHelper('Unit\Entity')->createMock('IC\Bundle\Base\RestBundle\Entity\Foo', 1);
        $resourceService           = $this->createResourceServiceMock($allowedMethodList, array('put' => $entity));
        $resourceTranscoderService = $this->createResourceTranscoderServiceMock($request->getPathInfo(), $resourceService);
        $serializerService         = $this->createSerializerServiceMock($entity, '{mock content}');
        $mediatorService           = $this->createMediatorServiceMock($request, $resourceService, $entity);
        $restService               = $this->createRestServiceMock($serializerService, $resourceTranscoderService, $authorizationService);

        $authorizationService->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(false));

        $restService->setAuthorizationService($authorizationService);
        $restService->put($request);
    }

    /**
     * Delete success
     */
    public function testDeleteSuccessPath()
    {
        $request                   = Request::create('/', 'DELETE');
        $allowedMethodList         = array('DELETE');
        $authorizationService      = $this->createMock('IC\Bundle\Base\SecurityBundle\Service\AuthorizationService');
        $entity                    = $this->getHelper('Unit\Entity')->createMock('IC\Bundle\Base\RestBundle\Entity\Foo', 1);
        $serializeContent          = '{mock serialize content}';
        $resourceService           = $this->createResourceServiceMock($allowedMethodList, array('put' => $entity));
        $resourceTranscoderService = $this->createResourceTranscoderServiceMock($request->getPathInfo(), $resourceService);
        $serializerService         = $this->createSerializerServiceMock($entity, $serializeContent);
        $restService               = $this->createRestServiceMock($serializerService, $resourceTranscoderService, $authorizationService);

        $resourceService->expects($this->any())
            ->method('get')
            ->with($request)
            ->will($this->returnValue($entity));

        $authorizationService->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $restService->setAuthorizationService($authorizationService);

        $response = $restService->delete($request);

        $this->assertHttpStatusAndContent($response, 200, $serializeContent);
    }

    /**
     * Test delete method not allowed
     */
    public function testDeleteMethodNotAllowed()
    {
        $request                   = Request::create('/', 'DELETE');
        $allowedMethodList         = array();
        $entity                    = $this->getHelper('Unit\Entity')->createMock('IC\Bundle\Base\RestBundle\Entity\Foo', 1);
        $resourceService           = $this->createResourceServiceMock($allowedMethodList, array('delete' => $entity));
        $resourceTranscoderService = $this->createResourceTranscoderServiceMock($request->getPathInfo(), $resourceService);
        $serializerService         = $this->createSerializerServiceMock();
        $authorizationService      = $this->createMock('IC\Bundle\Base\SecurityBundle\Service\AuthorizationService');
        $restService               = $this->createRestServiceMock($serializerService, $resourceTranscoderService, $authorizationService);
        $response                  = $restService->delete($request);

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals('HTTP method not allowed.', $response->getContent());
    }

    /**
     * Test delete fails when entity is not found
     */
    public function testDeleteEntityNotFound()
    {
        $request                   = Request::create('/', 'DELETE');
        $allowedMethodList         = array('DELETE');
        $entity                    = $this->getHelper('Unit\Entity')->createMock('IC\Bundle\Base\RestBundle\Entity\Foo', 1);
        $resourceService           = $this->createResourceServiceMock($allowedMethodList, array('delete' => $entity));
        $resourceTranscoderService = $this->createResourceTranscoderServiceMock($request->getPathInfo(), $resourceService);
        $serializerService         = $this->createSerializerServiceMock();
        $authorizationService      = $this->createMock('IC\Bundle\Base\SecurityBundle\Service\AuthorizationService');
        $restService               = $this->createRestServiceMock($serializerService, $resourceTranscoderService, $authorizationService);

        $resourceService->expects($this->once())
            ->method('get')
            ->with($request)
            ->will($this->returnValue(null));

        $response = $restService->delete($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Resource not found.', $response->getContent());
    }

    /**
     * Test delete fails: class not authorized
     */
    public function testDeleteAuthorizationRequired()
    {
        $request                   = Request::create('/', 'DELETE');
        $allowedMethodList         = array('DELETE');
        $entity                    = $this->getHelper('Unit\Entity')->createMock('IC\Bundle\Base\RestBundle\Entity\Foo', 1);
        $resourceService           = $this->createResourceServiceMock($allowedMethodList, array('delete' => $entity));
        $resourceTranscoderService = $this->createResourceTranscoderServiceMock($request->getPathInfo(), $resourceService);
        $serializerService         = $this->createSerializerServiceMock();
        $authorizationService      = $this->createMock('IC\Bundle\Base\SecurityBundle\Service\AuthorizationService');
        $restService               = $this->createRestServiceMock($serializerService, $resourceTranscoderService, $authorizationService);

        $resourceService->expects($this->exactly(2))
            ->method('get')
            ->with($request)
            ->will($this->returnValue($entity));

        $authorizationService->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(false));

        $response = $restService->delete($request);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Authorization required', $response->getContent());
    }

    /**
     * Mock an authorizationService instance
     *
     * @param string|null $action
     * @param string|null $className
     * @param bool        $expected
     *
     * @return \IC\Bundle\Base\SecurityBundle\Service\AuthorizationService
     */
    private function createAuthorizationServiceMock($action = null, $className = null, $expected = true)
    {
        $authorizationService = $this->createMock('IC\Bundle\Base\SecurityBundle\Service\AuthorizationService');

        if (null !== $action || null !== $className) {
            $authorizationService->expects($this->any())
                ->method('isGranted')
                ->with($action, $className)
                ->will($this->returnValue($expected));
        }

        return $authorizationService;
    }

    /**
     * Create a mediator service instance
     *
     * @param \Symfony\Component\HttpFoundation\Request          $request
     * @param \IC\Bundle\Base\RestBundle\Service\ResourceService $resourceService
     * @param mixed                                              $entity
     *
     * @return mixed
     */
    private function createMediatorServiceMock(Request $request, ResourceService $resourceService, $entity)
    {
        $entityRepository = $this->createMock('IC\Bundle\Base\ComponentBundle\Entity\Repository\EntityRepository');
        $mediatorService  = $this->createMock('IC\Bundle\Base\RestBundle\Service\MediatorService');

        $resourceService->expects($this->any())
            ->method('getMediatorService')
            ->will($this->returnValue($mediatorService));

        $resourceService->expects($this->any())
            ->method('getEntityRepository')
            ->will($this->returnValue($entityRepository));

        $mediatorService->expects($this->any())
            ->method('mediate')
            ->with($request, $entityRepository)
            ->will($this->returnValue($entity));

        return $mediatorService;
    }

    /**
     * Mock a ResourceService instance, its getAllowedMethodList method, and any additional
     * methods as specified by $methodCallExpectationMap
     *
     * @param array $allowedMethodList        The value to be retuend by getAllowedMethodList
     * @param array $methodCallExpectationMap Any additional methods to mock
     *
     * @return \IC\Bundle\Base\RestBundle\Service\ResourceService
     */
    private function createResourceServiceMock(array $allowedMethodList, array $methodCallExpectationMap = array())
    {
        $resourceService  = $this->createMock('IC\Bundle\Base\RestBundle\Service\ResourceService');
        $httpMethod       = key($methodCallExpectationMap);

        $resourceService->expects($this->any())
            ->method('getAllowedMethodList')
            ->will($this->returnValue($allowedMethodList));

        if (array_key_exists($httpMethod, $methodCallExpectationMap)) {
            $resourceService->expects($this->any())
                ->method('getClassName')
                ->will($this->returnValue($methodCallExpectationMap[$httpMethod]));
        }

        foreach ($methodCallExpectationMap as $method => $expectedReturnValue) {
            $resourceService->expects($this->any())
                ->method($method)
                ->will($this->returnValue($expectedReturnValue));
        }

        return $resourceService;
    }

    /**
     * Create a mock instance of ResourceTranscoderService
     *
     * @param string                                             $input  The expected input into the convertEndpointToService method
     * @param \IC\Bundle\Base\RestBundle\Service\ResourceService $output The expected output from convertEndpointToService
     *
     * @return \IC\Bundle\Base\RestBundle\Service\ResourceTranscoderService
     */
    private function createResourceTranscoderServiceMock($input, ResourceService $output)
    {
        $resourceTranscoderService = $this->createMock('IC\Bundle\Base\RestBundle\Service\ResourceTranscoderService');

        $resourceTranscoderService->expects($this->any())
            ->method('convertEndpointToService')
            ->with($this->equalTo($input))
            ->will($this->returnValue($output));

        return $resourceTranscoderService;
    }

    /**
     * Create a mock instance of SerializerInterface
     *
     * @param mixed $input  The expected input into the serialize method
     * @param mixed $output The expected output from the serialize method
     *
     * @return \JMS\Serializer\SerializerInterface
     */
    private function createSerializerServiceMock($input = null, $output = null)
    {
        $serializerService = $this->createMock('JMS\Serializer\SerializerInterface');

        $serializerService->expects($this->any())
            ->method('serialize')
            ->with($this->equalTo($input))
            ->will($this->returnValue($output));

        return $serializerService;
    }

    /**
     * Create, initialize, and return a new instance of RestService
     *
     * @param \JMS\Serializer\SerializerInterface                          $serializerService         The serializer dependency
     * @param \IC\Bundle\Base\RestBundle\Service\ResourceTranscoderService $resourceTranscoderService The resource transcoder dependency
     * @param \IC\Bundle\Base\SecurityBundle\Service\AuthorizationService  $authorizationService      The authorzation service dependency
     *
     * @return \IC\Bundle\Base\RestBundle\Service\RestService
     */
    private function createRestServiceMock(SerializerInterface $serializerService, ResourceTranscoderService $resourceTranscoderService, AuthorizationService $authorizationService)
    {
        $restService = new RestService();

        $restService->setSerializerService($serializerService);
        $restService->setResourceTranscoderService($resourceTranscoderService);
        $restService->setAuthorizationService($authorizationService);

        return $restService;
    }

    /**
     * Make assertions about the state of the given response
     *
     * @param \Symfony\Component\HttpFoundation\Response $response   The response object to inspect
     * @param integer                                    $statusCode The expected status code
     * @param string                                     $content    The expected content
     */
    private function assertHttpStatusAndContent(Response $response, $statusCode, $content)
    {
        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals($content, $response->getContent());

        $contentLength = $statusCode === 404 ? 0 : strlen($content);

        $this->assertEquals($contentLength, $response->headers->get('Content-length'));
    }
}
