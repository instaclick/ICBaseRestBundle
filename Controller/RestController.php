<?php
/**
 * @copyright 2012 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Rest Controller
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 * @author Juti Noppornpitak <jutin@nationalfibre.net>
 */
class RestController extends Controller
{
    /**
     * Filter action.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function filterAction(Request $request)
    {
        $restService = $this->container->get('ic_base_rest.service.rest');

        return $restService->filter($request);
    }

    /**
     * Post action.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        $restService = $this->container->get('ic_base_rest.service.rest');

        return $restService->post($request);
    }

    /**
     * Get action.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction(Request $request)
    {
        $restService = $this->container->get('ic_base_rest.service.rest');

        return $restService->get($request);
    }

    /**
     * Put action.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction(Request $request)
    {
        $restService = $this->container->get('ic_base_rest.service.rest');

        return $restService->put($request);
    }

    /**
     * Delete action.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request)
    {
        $restService = $this->container->get('ic_base_rest.service.rest');

        return $restService->delete($request);
    }
}
