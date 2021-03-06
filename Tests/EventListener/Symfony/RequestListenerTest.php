<?php
/**
 * @copyright 2013 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Tests\EventListener\Symfony;

use IC\Bundle\Base\RestBundle\EventListener\Symfony\RequestListener;
use IC\Bundle\Base\TestBundle\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Test for RequestListener
 *
 * @group Unit
 * @group EventListener
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 */
class RequestListenerTest extends TestCase
{
    /**
     * Test success situations
     *
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @dataProvider provideDataForSuccessRequest
     */
    public function testSuccessRequest($request)
    {
        $request->headers->add(array('Authorization' => 'mockAuthorizationToken'));

        $event = $this->onKernelRequest($request, 'ICBaseRestBundle_Rest_Index');

        $this->assertFalse($event->hasResponse(), 'Should not have a response yet.');
    }

    /**
     * Data Provider for method "testSuccessRequest"
     *
     * @return array
     */
    public function provideDataForSuccessRequest()
    {
        return array(
            array(Request::create('/', 'GET')),
            array(Request::create('/', 'DELETE')),
            array(Request::create('/', 'POST', array(), array(), array(), array(), 'instaclick=test')),
            array(Request::create('/', 'PUT', array(), array(), array(), array(), 'instaclick=test')),
            array(Request::create('/', 'GET', array('_format' => 'json'))),
        );
    }

    /**
     * Test failure situations.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request    HTTP Request
     * @param integer                                   $statusCode HTTP Status
     * @param string                                    $message    Response content
     *
     * @dataProvider provideDataForFailureRequest
     */
    public function testFailureRequest($request, $statusCode, $message)
    {
        $request->headers->add(array('Authorization' => 'mockAuthorizationToken'));

        $event = $this->onKernelRequest($request, 'ICBaseRestBundle_Rest_Index');

        $this->assertTrue($event->hasResponse(), 'Should have an error response.');
        $this->assertEquals($event->getResponse()->getStatusCode(), $statusCode);
        $this->assertEquals($event->getResponse()->getContent(), $message);
    }

    /**
     * Data Provider for method "testFailureRequest"
     *
     * @return array
     */
    public function provideDataForFailureRequest()
    {
        $missingAcceptHeaderGetRequest = Request::create('/', 'GET');
        $missingAcceptHeaderGetRequest->headers->remove('Accept');

        $missingAcceptHeaderDeleteRequest = Request::create('/', 'DELETE');
        $missingAcceptHeaderDeleteRequest->headers->remove('Accept');

        $missingContentTypeHeaderPostRequest = Request::Create('/', 'POST');
        $missingContentTypeHeaderPostRequest->headers->remove('Content-Type');

        $missingContentTypeHeaderPutRequest = Request::Create('/', 'PUT');
        $missingContentTypeHeaderPutRequest->headers->remove('Content-Type');

        return array(
            // Valid Method test
            array(
                Request::create('/', 'PATCH'),
                405,
                'The PATCH method is not supported.'
            ),
            array(
                Request::create('/', 'OPTIONS'),
                405,
                'The OPTIONS method is not supported.'
            ),
            array(
                Request::create('/', 'CONNECT'),
                405,
                'The CONNECT method is not supported.'
            ),

            // Valid Header test
            array(
                $missingAcceptHeaderGetRequest,
                412,
                'Missing header Accept in GET request.'
            ),
            array(
                $missingAcceptHeaderDeleteRequest,
                412,
                'Missing header Accept in DELETE request.'
            ),
            array(
                $missingContentTypeHeaderPostRequest,
                412,
                'Missing header Content-Type in POST request.'
            ),
            array(
                $missingContentTypeHeaderPutRequest,
                412,
                'Missing header Content-Type in PUT request.'
            ),

            // Valid Body test
            array(
                Request::create('/', 'GET', array(), array(), array(), array(), 'instaclick=test'),
                400,
                'Request body cannot be set for GET request.'
            ),
            array(
                Request::create('/', 'DELETE', array(), array(), array(), array(), 'instaclick=test'),
                400,
                'Request body cannot be set for DELETE request.'
            ),
            array(
                Request::create('/', 'POST'),
                400,
                'Request body must be set for POST request.'
            ),
            array(
                Request::create('/', 'PUT'),
                400,
                'Request body must be set for PUT request.'
            ),

            // Valid Format test
            array(
                Request::create('/', 'POST', array(), array(), array(), array('HTTP_ACCEPT' => 'image/jpeg'), 'instaclick=test'),
                415,
                'Unsupported media type.'
            ),
        );
    }

    /**
     * Test handle/not handle situations.
     *
     * @param string  $routeName   Route name
     * @param integer $requestType Symfony HttpKernel Request type (MASTER or SUB request)
     *
     * @dataProvider provideDataForIsValidEvent
     */
    public function testIsValidEvent($routeName, $requestType)
    {
        $request    = Request::create('/', 'GET');
        $request->headers->add(array('Authorization' => 'mockAuthorizationToken'));

        $routerMock             = $this->createRouterMock($request->getPathInfo(), $routeName);
        $accessTokenServiceMock = $this->createAccessTokenServiceMock();
        $listener               = $this->createRequestListener($routerMock, $accessTokenServiceMock);
        $event                  = $this->createGetResponseEvent($request, $requestType);
        $result                 = $listener->onKernelRequest($event);

        $this->assertFalse($event->hasResponse(), 'Should not have a response yet.');
        $this->assertNull($result);
    }

    /**
     * Data Provider for method "testIsValidEvent"
     *
     * @return array
     */
    public function provideDataForIsValidEvent()
    {
        return array(
            array('ICBaseRestBundle_Rest_Index', HttpKernelInterface::SUB_REQUEST),
            array('Black_Hole', HttpKernelInterface::MASTER_REQUEST),
        );
    }

    /**
     * Test Router failure
     *
     */
    public function testRouterFailure()
    {
        $request    = Request::create('/', 'GET');
        $request->headers->add(array('Authorization' => 'mockAuthorizationToken'));

        $routerMock             = $this->createFailureRouterMock($request->getPathInfo());
        $accessTokenServiceMock = $this->createAccessTokenServiceMock();
        $listener               = $this->createRequestListener($routerMock, $accessTokenServiceMock);
        $event                  = $this->createGetResponseEvent($request, HttpKernelInterface::MASTER_REQUEST);
        $result                 = $listener->onKernelRequest($event);

        $this->assertFalse($event->hasResponse(), 'Should not have a response yet.');
        $this->assertNull($result);
    }

    /**
     * Mock failure router
     *
     * @param string $pathInfo HTTP path info
     *
     * @return \Symfony\Component\Routing\RouterInterface
     */
    private function createFailureRouterMock($pathInfo)
    {
        $router = $this->createMock('Symfony\Component\Routing\RouterInterface');

        $router
            ->expects($this->any())
            ->method('match')
            ->with($pathInfo)
            ->will($this->throwException(new \Exception('Generic exception.')));

        return $router;
    }

    /**
     * Mock router
     *
     * @param string $pathInfo  HTTP path info
     * @param string $routeName Route name
     *
     * @return \Symfony\Component\Routing\RouterInterface
     */
    private function createRouterMock($pathInfo, $routeName)
    {
        $router = $this->createMock('Symfony\Component\Routing\RouterInterface');

        $router
            ->expects($this->any())
            ->method('match')
            ->with($pathInfo)
            ->will($this->returnValue(array('_route' => $routeName)));

        return $router;
    }

    /**
     * Execute onKernelRequest call on listener
     *
     * @param \Symfony\Component\HttpFoundation\Request $request   HTTP Request
     * @param string                                    $routeName Route name
     *
     * @return \Symfony\Component\HttpKernel\Event\GetResponseEvent
     */
    private function onKernelRequest($request, $routeName)
    {
        $routerMock         = $this->createRouterMock($request->getPathInfo(), $routeName);
        $accessTokenService = $this->createAccessTokenServiceMock();
        $listener           = $this->createRequestListener($routerMock, $accessTokenService);
        $event              = $this->createGetResponseEvent($request, HttpKernelInterface::MASTER_REQUEST);

        $listener->onKernelRequest($event);

        return $event;
    }

    /**
     * Mock accessTokenService
     *
     * @return \IC\Bundle\Base\SecurityBundle\Service\AccessTokenServiceInterface
     */
    private function createAccessTokenServiceMock()
    {
        $accessTokenService = $this->createMock('IC\Bundle\Base\SecurityBundle\Service\AccessTokenServiceInterface');
        $user               = $this->createMock('\Symfony\Component\Security\Core\User\UserInterface');

        $accessTokenService->expects($this->any())
            ->method('validate')
            ->will($this->returnValue($user));

        return $accessTokenService;
    }

    /**
     * Create a GetResponseEvent
     *
     * @param \Symfony\Component\HttpFoundation\Request $request     HTTP Request
     * @param integer                                   $requestType Symfony HttpKernel Request type (MASTER or SUB request)
     *
     * @return \Symfony\Component\HttpKernel\Event\GetResponseEvent
     */
    private function createGetResponseEvent($request, $requestType)
    {
        return new GetResponseEvent(
            $this->createMock('Symfony\Component\HttpKernel\HttpKernelInterface'),
            $request,
            $requestType
        );
    }

    /**
     * Create a RequestListener instance configured
     *
     * @param \Symfony\Component\Routing\RouterInterface                         $router             Symfony Router
     * @param \IC\Bundle\Base\SecurityBundle\Service\AccessTokenServiceInterface $accessTokenService AccessTokenService Interface
     *
     * @return \IC\Bundle\Base\RestBundle\EventListener\Symfony\RequestListener
     */
    private function createRequestListener($router, $accessTokenService)
    {
        $listener = new RequestListener();

        $listener->setRouter($router);

        return $listener;
    }
}
