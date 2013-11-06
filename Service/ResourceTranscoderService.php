<?php
/**
 * @copyright 2012 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * Resource Transcoder Service
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 */
class ResourceTranscoderService extends ContainerAware
{
    /**
     * Convert an endpoint to a Resource Service
     *
     * @param string $endpoint
     *
     * @return \IC\Bundle\Base\RestBundle\Service\ResourceServiceInterface
     */
    public function convertEndpointToService($endpoint)
    {
        $routerService = $this->container->get('router');
        $routeInfo     = $routerService->match($endpoint);

        $servicePrefix  = 'ic';
        $packageName    = $routeInfo['packageName'];
        $subPackageName = $routeInfo['subPackageName'];
        $entityName     = $routeInfo['entityName'];

        $serviceIdentifier = sprintf('%s_%s_%s.rest.%s', $servicePrefix, $packageName, $subPackageName, $entityName);

        return $this->container->get($serviceIdentifier);
    }

    /**
     * Convert a Resource Service to an endpoint.
     *
     * @param \IC\Bundle\Base\RestBundle\Service\ResourceServiceInterface $resourceService
     *
     * @return string
     */
    public function convertServiceToEndpoint(ResourceServiceInterface $resourceService)
    {
        $routerService = $this->container->get('router');

        return $routerService->generate(
            'ICBaseRestBundle_Rest_Filter',
            array(
                'packageName'    => $resourceService->getPackageName(),
                'subPackageName' => $resourceService->getSubPackageName(),
                'entityName'     => $resourceService->getEntityName()
            ),
            true
        );
    }
}
