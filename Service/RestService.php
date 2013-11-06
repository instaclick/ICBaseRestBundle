<?php
/**
 * @copyright 2012 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use IC\Bundle\Base\ComponentBundle\Entity\Entity;
use IC\Bundle\Base\SecurityBundle\Service\AuthorizationService;
use IC\Bundle\Base\RestBundle\Service\ResourceService;

/**
 * Rest Service
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 * @author Yuan Xie <shayx@nationalfibre.net>
 */
class RestService
{
    /**
     * @var \JMS\Serializer\SerializerInterface
     */
    protected $serializerService;

    /**
     * @var \IC\Bundle\Base\RestBundle\Service\ResourceTranscoderService
     */
    protected $resourceTranscoderService;

    /**
     * @var \IC\Bundle\Base\SecurityBundle\Service\AuthorizationService
     */
    protected $authorizationService;

    /**
     * Define the SerializerService.
     *
     * @param \JMS\Serializer\SerializerInterface $serializerService
     */
    public function setSerializerService(SerializerInterface $serializerService)
    {
        $this->serializerService = $serializerService;
    }

    /**
     * Define the ResourceTranscoderService.
     *
     * @param \IC\Bundle\Base\RestBundle\Service\ResourceTranscoderService $resourceTranscoderService
     */
    public function setResourceTranscoderService(ResourceTranscoderService $resourceTranscoderService)
    {
        $this->resourceTranscoderService = $resourceTranscoderService;
    }

    /**
     * Define the authorization service.
     *
     * @param \IC\Bundle\Base\SecurityBundle\Service\AuthorizationService $authorizationService
     */
    public function setAuthorizationService(AuthorizationService $authorizationService)
    {
        $this->authorizationService = $authorizationService;
    }

    /**
     * Filter a Resource collection and return a response with serialized content.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function filter(Request $request)
    {
        $resourceEndpoint = $request->getPathInfo();
        $resourceService  = $this->resourceTranscoderService->convertEndpointToService($resourceEndpoint);

        if ( ! $this->isMethodAllowed($resourceService, $request->getMethod())) {
            return $this->createErrorResponse('HTTP method not allowed.', 405);
        }

        $className = $resourceService->getClassName();

        if ( ! $this->isObjectClassAccessAuthorized("VIEW", $className)) {
            return $this->createErrorResponse('Authorization required.', 401);
        }

        $resultList = $resourceService->filter($request);
        $content    = $this->serialize($resultList, $request->getRequestFormat());
        $headerList = $this->createHeaderList($request, $resourceService);

        return $this->createSuccessResponse($content, 200, $headerList);
    }

    /**
     * Insert entity and return a response with serialized content.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function post(Request $request)
    {
        $resourceEndpoint = $request->getPathInfo();
        $resourceService  = $this->resourceTranscoderService->convertEndpointToService($resourceEndpoint);

        if ( ! $this->isMethodAllowed($resourceService, $request->getMethod())) {
            return $this->createErrorResponse('HTTP method not allowed.', 405);
        }

        $className = $resourceService->getClassName();

        if ( ! $this->isObjectClassAccessAuthorized("CREATE", $className)) {
            return $this->createErrorResponse('Authorization required.', 401);
        }

        $resultList = $resourceService->post($request);
        $content    = $this->serialize($resultList, $request->getRequestFormat());
        $statusCode = ($resultList instanceof Entity) ? 201 : 400;
        $headerList = $this->createHeaderList($request, $resourceService);

        if ($statusCode === 201) {
            $endpoint = $this->resourceTranscoderService->convertServiceToEndpoint($resourceService);

            $headerList['Location'] = $endpoint . '/' . $resultList->getId();
        }

        return $this->createSuccessResponse($content, $statusCode, $headerList);
    }

    /**
     * Retrieve a Resource and return a response with serialized content.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function get(Request $request)
    {
        $resourceEndpoint = $request->getPathInfo();
        $resourceService  = $this->resourceTranscoderService->convertEndpointToService($resourceEndpoint);

        if ( ! $this->isMethodAllowed($resourceService, $request->getMethod())) {
            return $this->createErrorResponse('HTTP method not allowed.', 405);
        }

        $entity = $resourceService->get($request);

        if ( ! $entity) {
            return $this->createErrorResponse('Resource not found.', 404);
        }

        $className = $resourceService->getClassName();

        if ( ! $this->isObjectClassAccessAuthorized("VIEW", $className) &&
             ! $this->isObjectClassAccessAuthorized("VIEW", $entity)) {
            return $this->createErrorResponse('Authorization required', 401);
        }

        $content = $this->serialize($entity, $request->getRequestFormat());
        $headerList = $this->createHeaderList($request, $resourceService);

        return $this->createSuccessResponse($content, 200, $headerList);
    }

    /**
     * Update entity (PUT) and return s response with serialized content.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function put(Request $request)
    {
        $resourceEndpoint = $request->getPathInfo();
        $resourceService  = $this->resourceTranscoderService->convertEndpointToService($resourceEndpoint);

        if ( ! $this->isMethodAllowed($resourceService, $request->getMethod())) {
            return $this->createErrorResponse('HTTP method not allowed.', 405);
        }

        $mediatorService  = $resourceService->getMediatorService();
        $entityRepository = $resourceService->getEntityRepository();

        $entity = $mediatorService->mediate($request, $entityRepository);

        if (is_array($entity)) {
            $errorList = $this->getErrorList($entity);

            return $this->createErrorResponse(json_encode($errorList), $entity['code']);
        }

        if ( ! $entity || ! $entity instanceof Entity) {
            return $this->createErrorResponse('Resource not found.', 404);
        }

        $className = $resourceService->getClassName();

        if ( ! $this->isObjectClassAccessAuthorized("EDIT", $className) &&
             ! $this->isObjectClassAccessAuthorized("EDIT", $entity)) {
            return $this->createErrorResponse('Authorization required', 401);
        }

        $resultList = $resourceService->put($request);
        $content    = $this->serialize($resultList, $request->getRequestFormat());
        $statusCode = ($resultList instanceof Entity) ? 200: 400;
        $headerList = $this->createHeaderList($request, $resourceService);

        return $this->createSuccessResponse($content, $statusCode, $headerList);
    }

    /**
     * Delete a Resource and return a response with serialized content.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function delete(Request $request)
    {
        $resourceEndpoint = $request->getPathInfo();
        $resourceService  = $this->resourceTranscoderService->convertEndpointToService($resourceEndpoint);

        if ( ! $this->isMethodAllowed($resourceService, $request->getMethod())) {
            return $this->createErrorResponse('HTTP method not allowed.', 405);
        }

        $response = $this->get($request);

        if ($response->getStatusCode() == 404) {
            return $this->createErrorResponse('Resource not found.', 404);
        }

        $entity    = $resourceService->get($request);
        $className = $resourceService->getClassName();

        if ( ! $this->isObjectClassAccessAuthorized("EDIT", $className) &&
             ! $this->isObjectClassAccessAuthorized("EDIT", $entity)) {
            return $this->createErrorResponse('Authorization required', 401);
        }

        $resourceService->delete($request);

        return $response;
    }

    /**
     * Check if request method is allowed by resource service.
     *
     * @param \IC\Bundle\Base\RestBundle\Service\ResourceServiceInterface $resourceService Resource service
     * @param string                                                      $method          Method name
     *
     * @return boolean
     */
    private function isMethodAllowed(ResourceServiceInterface $resourceService, $method)
    {
        $allowedMethodList = $resourceService->getAllowedMethodList();

        return in_array($method, $allowedMethodList);
    }

    /**
     * Create an error Response.
     *
     * @param string  $errorList  Json string of a list of errors
     * @param integer $statusCode Status code
     * @param array   $headerList Headers
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function createErrorResponse($errorList, $statusCode = 400, $headerList = array())
    {
        $headerList['Content-Type'] = 'text/plain';

        return new Response($errorList, $statusCode, $headerList);
    }

    /**
     * Create a success Response
     *
     * @param string  $content    Body of the response
     * @param integer $statusCode Status code
     * @param array   $headerList Headers
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function createSuccessResponse($content, $statusCode, array $headerList)
    {
        $headerList['Content-length'] = strlen($content);

        return new Response($content, $statusCode, $headerList);
    }

    /**
     * Create header for a HTTP response
     *
     * @param \Symfony\Component\HttpFoundation\Request          $request
     * @param \IC\Bundle\Base\RestBundle\Service\ResourceService $resourceService
     *
     * @return array
     */
    private function createHeaderList(Request $request, ResourceService $resourceService)
    {
        return array(
            'Content-type'   => $request->getMimeType($request->getRequestFormat()),
            'Allow'          => implode(', ', $resourceService->getAllowedMethodList())
        );
    }

    /**
     * Checks for Object scope authorization.
     *
     * @param string $action The action name.
     * @param mixed  $entity The domain object instance or the name of the class
     *
     * @return boolean
     */
    private function isObjectClassAccessAuthorized($action, $entity)
    {
        return $this->authorizationService->isGranted($action, $entity);
    }

    /**
     * Serialize $data into $format.
     *
     * @param mixed  $data
     * @param string $format
     *
     * @return string
     */
    private function serialize($data, $format)
    {
        $context = new SerializationContext();
        $context->setAttribute('translatable', true);
        $context->setSerializeNull(true);

        return $this->serializerService->serialize($data, $format, $context);
    }

    /**
     * Return an list of error messages
     *
     * @param array $feedback
     *
     * @return array
     */
    private function getErrorList($feedback)
    {
        $errorList = array();

        if (array_key_exists('previous', $feedback)) {
            $errorList[] = $feedback['previous']['message'];

            return $errorList;
        }

        foreach ($feedback['validator'] as $validator) {
            $errorList[] = $validator->getMessage();
        }

        return $errorList;
    }
}
