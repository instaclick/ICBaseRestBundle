<?php
/**
 * @copyright 2012 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\EventListener\Symfony;

use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use IC\Bundle\Base\SecurityBundle\Service\AccessTokenServiceInterface;

/**
 * The request listener to intercept and validate possible REST requests.
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 * @author Juti Noppornpitak <jutin@nationalfibre.net>
 * @author Anthon Pang <anthonp@nationalfibre.net>
 * @author Oleksii Strutsynskyi <oleksiis@nationalfibre.net>
 * @author Paul Munson <pmunson@nationalfibre.net>
 */
class RequestListener
{
    /**
     * Scope value
     */
    const SCOPE = "api";
    /**
     * @var array Allowed HTTP Methods
     */
    protected $allowedMethods = array('GET', 'POST', 'PUT', 'DELETE');

    /**
     * @var \Symfony\Component\Routing\RouterInterface The router
     */
    protected $router;

    /**
     * @var \IC\Bundle\Base\SecurityBundle\Service\AccessTokenServiceInterface
     */
    protected $accessTokenService;

    /**
     * Define the router service
     *
     * @param \Symfony\Component\Routing\RouterInterface $router
     */
    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Listen for a browser pre-fetch (or pre-render) request
     *
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event Symfony Kernel event
     *
     * @return mixed
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ( ! $this->isValidEvent($event)) {
            return;
        }

        $request       = $event->getRequest();
        $requestMethod = strtoupper($request->getMethod());

        switch (false) {
            case $this->isValidMethod($request->getMethod()):
                $event->setResponse(
                    new Response(sprintf('The %s method is not supported.', $requestMethod), 405)
                );
                break;
            case $this->isAuthenticatedRequest($request, $event):
                $event->setResponse(
                    new Response(sprintf('The request is not authenticated.', $requestMethod), 403)
                );
                break;
            case $this->isValidHeader($request):
                $httpHeader = in_array($requestMethod, array('GET', 'DELETE')) ? 'Accept' : 'Content-Type';

                $event->setResponse(
                    new Response(sprintf('Missing header %s in %s request.', $httpHeader, $requestMethod), 412)
                );

                return;
            case $this->isValidBody($request):
                $messageVerb = in_array($requestMethod, array('GET', 'DELETE')) ? 'cannot' : 'must';

                $event->setResponse(
                    new Response(sprintf('Request body %s be set for %s request.', $messageVerb, $requestMethod), 400)
                );

                return;
            default:
                // Do nothing
        }

        $format = $this->getFormat($request);

        if ( ! $format) {
            $event->setResponse(
                new Response('Unsupported media type.', 415)
            );

            return;
        }


        $request->setRequestFormat($format);
    }

    /**
     * Define the token service that generate OAuth2 tokens
     *
     * @param \IC\Bundle\Base\SecurityBundle\Service\AccessTokenServiceInterface $accessTokenService
     */
    public function setAccessTokenService(AccessTokenServiceInterface $accessTokenService)
    {
        $this->accessTokenService = $accessTokenService;
    }

    /**
     * Check if the current event/request is on the given route.
     *
     * @param GetResponseEvent $event Event
     *
     * @return boolean
     */
    protected function isValidEvent(GetResponseEvent $event)
    {
        // Is it a master request (not an ESI request)
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return false;
        }

        try {
            $routeInfo = $this->router->match($event->getRequest()->getPathInfo());

            return (strpos($routeInfo['_route'], 'ICBaseRestBundle_Rest_') !== false);
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * Check for valid HTTP method.
     *
     * @param string $method HTTP Method (GET, POST, PUT, ...)
     *
     * @return boolean
     */
    protected function isValidMethod($method)
    {
        return in_array($method, $this->allowedMethods, true);
    }

    /**
     * Check for valid HTTP header.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return boolean
     */
    protected function isValidHeader(Request $request)
    {
        // Validate According to Method
        switch (strtoupper($request->getMethod())) {
            case 'GET':
            case 'DELETE':
                // Header Accept validation
                return $request->headers->has('Accept');
            case 'POST':
            case 'PUT':
        }

        // Header Content-Type validation
        return $request->headers->has('Content-Type');
    }

    /**
     * Check if the request has valid content.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return boolean
     */
    protected function isValidBody(Request $request)
    {
        $requestBody = $request->getContent();

        // Validate According to Method
        switch (strtoupper($request->getMethod())) {
            case 'GET':
            case 'DELETE':
                // Header Accept validation
                return empty($requestBody);
            case 'POST':
            case 'PUT':
        }

        // Header Content-Type validation
        return ( ! empty($requestBody));
    }

    /**
     * Check for an authenticated Request (with an Auth HTTP header).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return boolean
     */
    protected function isAuthenticatedRequest(Request $request)
    {
        if ( ! $request->headers->has('Authorization')) {
            return false;
        }

        $tokenString = $request->headers->get('Authorization');
        $user        = $this->accessTokenService->validate($tokenString, self::SCOPE);

        if ($user instanceof UserInterface) {
            return true;
        }

        return false;
    }


    /**
     * Get preferable format for Request
     *
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return string
     */
    protected function getFormat(Request $request)
    {
        $accept = AcceptHeader::fromString($request->headers->get('Accept'));
        $format = $request->get('_format');

        if ($format && $request->getMimeType($format)) {
            return $format;
        }

        $preferableMimeTypeList = array(
            'application/json',
            'application/xml',
        );

        foreach ($preferableMimeTypeList as $preferableMimeType) {
            if ($accept->has($preferableMimeType) && $request->getFormat($preferableMimeType)) {
                return $request->getFormat($preferableMimeType);
            }
        }

        return null;
    }
}
