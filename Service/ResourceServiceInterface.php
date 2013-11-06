<?php
/**
 * @copyright 2012 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Service;

use Symfony\Component\HttpFoundation\Request;

use IC\Bundle\Base\ComponentBundle\Entity\Repository\EntityRepository;

/**
 * Resource Service Interface
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 */
interface ResourceServiceInterface
{
    /**
     * Retrieve the list of allowed methods over this resource.
     *
     * @return array
     */
    public function getAllowedMethodList();

    /**
     * Define the allowed method list.
     *
     * @param array $allowedMethodList
     */
    public function setAllowedMethodList(array $allowedMethodList);

    /**
     * Retrieve the Entity Service.
     *
     * @return \IC\Bundle\Base\ComponentBundle\Entity\Repository\EntityRepository
     */
    public function getEntityRepository();

    /**
     * Define the Entity Service.
     *
     * @param \IC\Bundle\Base\ComponentBundle\Entity\Repository\EntityRepository $entityRepository
     */
    public function setEntityRepository(EntityRepository $entityRepository);

    /**
     * Define the Mediator Service.
     *
     * @param \IC\Bundle\Base\RestBundle\Service\MediatorService $mediatorService
     */
    public function setMediatorService(MediatorService $mediatorService);

    /**
     * Retrieves the Mediator Service.
     *
     * @return \IC\Bundle\Base\RestBundle\Service\MediatorService
     */
    public function getMediatorService();

    /**
     * Retrieve the package name.
     *
     * @return string
     */
    public function getPackageName();

    /**
     * Retrieve the sub-package name.
     *
     * @return string
     */
    public function getSubPackageName();

    /**
     * Retrieve the entity name.
     *
     * @return string
     */
    public function getEntityName();

    /**
     * Retrieve the entity fully qualified class name.
     *
     * @return string
     */
    public function getClassName();

    /**
     * Filter the resource collection.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return object
     */
    public function filter(Request $request);

    /**
     * Create a new resource.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return objects
     */
    public function post(Request $request);

    /**
     * Retrieve an existent resource.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function get(Request $request);

    /**
     * Update an existent resource.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function put(Request $request);

    /**
     * Delete an existent resource
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function delete(Request $request);
}
