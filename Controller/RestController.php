<?php
/**
 * @copyright 2012 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function filterAction()
    {
        $restService = $this->container->get('ic_base_rest.service.rest');

        return $restService->filter($this->getRequest());
    }

    /**
     * Post action.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction()
    {
        $restService = $this->container->get('ic_base_rest.service.rest');

        return $restService->post($this->getRequest());
    }

    /**
     * Get action.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction()
    {
        $restService = $this->container->get('ic_base_rest.service.rest');

        return $restService->get($this->getRequest());
    }

    /**
     * Put action.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction()
    {
        $restService = $this->container->get('ic_base_rest.service.rest');

        return $restService->put($this->getRequest());
    }

    /**
     * Delete action.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction()
    {
        $restService = $this->container->get('ic_base_rest.service.rest');

        return $restService->delete($this->getRequest());
    }
}
