<?php
/**
 * @copyright 2012 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Service;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use IC\Bundle\Base\ComponentBundle\Entity\Repository\EntityRepository;
use IC\Bundle\Base\ComponentBundle\Exception\ServiceException;

/**
 * Resource Service
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 */
class ResourceService implements ResourceServiceInterface
{
    /**
     * @var array
     */
    protected $allowedMethodList;

    /**
     * @var \IC\Bundle\Base\ComponentBundle\Entity\Repository\EntityRepository
     */
    protected $entityRepository;

    /**
     * @var \IC\Bundle\Base\RestBundle\Service\MediatorService
     */
    protected $mediatorService;

    /**
     * {@inheritdoc}
     */
    public function getAllowedMethodList()
    {
        return $this->allowedMethodList;
    }

    /**
     * {@inheritdoc}
     */
    public function setAllowedMethodList(array $allowedMethodList)
    {
        $this->allowedMethodList = $allowedMethodList;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityRepository()
    {
        return $this->entityRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityRepository(EntityRepository $entityRepository)
    {
        $this->entityRepository = $entityRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function setMediatorService(MediatorService $mediatorService)
    {
        $this->mediatorService = $mediatorService;
    }

    /**
     * {@inheritdoc}
     */
    public function getMediatorService()
    {
        return $this->mediatorService;
    }

    /**
     * {@inheritdoc}
     */
    public function getPackageName()
    {
        return $this->entityRepository->getPackageName();
    }

    /**
     * {@inheritdoc}
     */
    public function getSubPackageName()
    {
        return $this->entityRepository->getSubPackageName();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityName()
    {
        return $this->entityRepository->getEntityName();
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName()
    {
        return $this->entityRepository->getClassName();
    }

    /**
     * {@inheritdoc}
     */
    public function filter(Request $request)
    {
        $criteriaBuilder = new Filter\CriteriaBuilder($this->entityRepository);
        $criteria        = $criteriaBuilder->build($request->query->all());
        $paginator       = new Paginator($criteria->getQuery());
        $firstResult     = $criteria->getFirstResult() + 1; // Originally 0-based index

        // Building filter result
        $filterResult = new Filter\Result();

        $totalPages  = ceil($paginator->count() / $criteria->getMaxResults());
        $currentPage = ceil($firstResult / $criteria->getMaxResults());

        $filterResult->setTotalPages($totalPages);
        $filterResult->setCurrentPage($request->get('page', $currentPage));
        $filterResult->setTotalResults($paginator->count());
        $filterResult->setMaxResults($criteria->getMaxResults());
        $filterResult->setFirstResult($firstResult);
        $filterResult->setResultList($paginator->getIterator());

        return $filterResult;
    }

    /**
     * {@inheritdoc}
     */
    public function post(Request $request)
    {
        $entity = $this->mediatorService->mediate($request, $this->entityRepository);

        if (is_array($entity)) {
            return $entity;
        }

        // Persist Entity
        $this->entityRepository->post($entity);

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function get(Request $request)
    {
        $id = $request->get('id');

        return $this->entityRepository->get($id);
    }

    /**
     * {@inheritdoc}
     */
    public function put(Request $request)
    {
        $entity = $this->mediatorService->mediate($request, $this->entityRepository);

        if (is_array($entity)) {
            return $entity;
        }

        // Persist Entity
        $this->entityRepository->put($entity);

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Request $request)
    {
        try {
            $id = $request->get('id');

            $this->entityRepository->delete($id);
        } catch (ServiceException $exception) {
            return false;
        }

        return true;
    }
}
