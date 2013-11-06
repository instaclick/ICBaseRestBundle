<?php
/**
 * @copyright 2013 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Tests\Service;

use IC\Bundle\Base\RestBundle\Service\ResourceTranscoderService;
use IC\Bundle\Base\TestBundle\Test\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Test for ResourceTranscoderService
 *
 * @group Unit
 * @group Service
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 */
class ResourceTranscoderServiceTest extends TestCase
{
    /**
     * Test for method "convertEndpointToService".
     *
     * @param string $expected  Expected service name
     * @param string $pathInfo  HTTP path info
     * @param array  $routeInfo Route info array
     *
     * @dataProvider provideDataForConvertEndpointToService
     */
    public function testConvertEndpointToService($expected, $pathInfo, $routeInfo)
    {
        $routerMock    = $this->createRouterMock($pathInfo, $routeInfo);
        $containerMock = $this->createContainerMock($routerMock);
        $service       = $this->createResourceTranscoderService($containerMock);

        $containerMock
            ->expects($this->at(1))
            ->method('get')
            ->will($this->returnArgument(0));

        $result = $service->convertEndpointToService($pathInfo);

        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for "testConvertEndpointToService"
     *
     * @return array
     */
    public function provideDataForConvertEndpointToService()
    {
        return array(
            array(
                'ic_base_rest.rest.entity',
                '/api/base/rest/entity',
                array(
                    'packageName'    => 'base',
                    'subPackageName' => 'rest',
                    'entityName'     => 'entity',
                )
            )
        );
    }

    /**
     * Test for method "convertServiceToEndpoint".
     *
     * @param string $pathInfo  HTTP path info
     * @param array  $routeInfo Route info array
     *
     * @dataProvider provideDataForConvertServiceToEndpoint
     */
    public function testConvertServiceToEndpoint($pathInfo, $routeInfo)
    {
        $routerMock    = $this->createRouterMock($pathInfo, $routeInfo);
        $containerMock = $this->createContainerMock($routerMock);
        $serviceMock   = $this->createResourceServiceMock($routeInfo);
        $service       = $this->createResourceTranscoderService($containerMock);

        $result = $service->convertServiceToEndpoint($serviceMock);

        $this->assertEquals($pathInfo, $result);
    }

    /**
     * Data provider for "testConvertServiceToEndpoint"
     *
     * @return array
     */
    public function provideDataForConvertServiceToEndpoint()
    {
        return array(
            array(
                '/api/base/rest/entity',
                array(
                    'packageName'    => 'base',
                    'subPackageName' => 'rest',
                    'entityName'     => 'entity',
                )
            )
        );
    }

    /**
     * Create Router mock
     *
     * @param string $pathInfo  HTTP path info
     * @param array  $routeInfo Route info array
     *
     * @return \Symfony\Component\Routing\RouterInterface
     */
    private function createRouterMock($pathInfo, array $routeInfo)
    {
        $router = $this->createMock('Symfony\Component\Routing\RouterInterface');

        $router
            ->expects($this->any())
            ->method('match')
            ->with($pathInfo)
            ->will($this->returnValue($routeInfo));

        $router
            ->expects($this->any())
            ->method('generate')
            ->with('ICBaseRestBundle_Rest_Filter', $routeInfo, true)
            ->will($this->returnValue($pathInfo));

        return $router;
    }

    /**
     * Create Container mock
     *
     * @param \Symfony\Component\Routing\RouterInterface $router Symfony Router
     *
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private function createContainerMock(RouterInterface $router)
    {
        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $container
            ->expects($this->at(0))
            ->method('get')
            ->with('router')
            ->will($this->returnValue($router));

        return $container;
    }

    /**
     * Create ResourceService mock
     *
     * @param array $routeInfo Route info array
     *
     * @return \IC\Bundle\Base\RestBundle\Service\ResourceServiceInterface
     */
    private function createResourceServiceMock(array $routeInfo)
    {
        $service = $this->createMock('IC\Bundle\Base\RestBundle\Service\ResourceServiceInterface');

        $service
            ->expects($this->once())
            ->method('getPackageName')
            ->will($this->returnValue($routeInfo['packageName']));

        $service
            ->expects($this->once())
            ->method('getSubPackageName')
            ->will($this->returnValue($routeInfo['subPackageName']));

        $service
            ->expects($this->once())
            ->method('getEntityName')
            ->will($this->returnValue($routeInfo['entityName']));

        return $service;
    }

    /**
     * Create ResourceTranscoderService
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container Symfony DIC
     *
     * @return \IC\Bundle\Base\RestBundle\Service\ResourceTranscoderService
     */
    private function createResourceTranscoderService(ContainerInterface $container)
    {
        $service = new ResourceTranscoderService();

        $service->setContainer($container);

        return $service;
    }
}
