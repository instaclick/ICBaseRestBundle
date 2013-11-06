<?php
/**
 * @copyright 2012 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Service\Filter;

use Doctrine\ORM\Query\Parameter;

use IC\Bundle\Base\ComponentBundle\Entity\Repository\EntityRepository;

/**
 * Criteria builder
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 */
class CriteriaBuilder
{
    /**
     * Maximum allowed returned results on an API request.
     */
    const MAX_RESULTS = 500;

    /**
     * @var \IC\Bundle\Base\ComponentBundle\Entity\Repository\EntityRepository
     */
    protected $entityRepository;

    /**
     * @var array
     */
    protected $keywordFieldList = array(
        '_format',
        'entityName',
        'page',
        'firstResult',
        'maxResults',
        'orderBy',
        'packageName',
        'page',
        'subPackageName',
    );

    /**
     * Constructor.
     *
     * @param \IC\Bundle\Base\ComponentBundle\Entity\Repository\EntityRepository $entityRepository
     */
    public function __construct(EntityRepository $entityRepository)
    {
        $this->entityRepository = $entityRepository;
    }

    /**
     * Build filter criteria.
     *
     * @param array $data
     *
     * @return \IC\Bundle\Base\ComponentBundle\Entity\Filter\Criteria
     */
    public function build(array $data)
    {
        $originalData = $data;
        $filteredData = array_diff_key($originalData, array_flip($this->keywordFieldList));

        $classMetadata = $this->entityRepository->getClassMetadata();
        $criteria      = $this->entityRepository->newCriteria('e');

        // Build WHERE predicates
        $whereClauseBuilder = new WhereClauseBuilder($classMetadata);
        $whereClause = $whereClauseBuilder->build($filteredData);

        if ($whereClause->count()) {
            // Append where clause and parameters, since there may have existent ones already
            $parameters = $this->buildParameters($criteria->getParameters(), $filteredData);

            $criteria->andWhere($whereClause);
            $criteria->setParameters($parameters);
        }

        // Build ORDER BY predicates
        $orderByArray         = isset($originalData['orderBy']) ? $originalData['orderBy'] : array();
        $orderByClauseBuilder = new OrderByClauseBuilder($classMetadata);
        $orderByClause        = $orderByClauseBuilder->build($orderByArray);

        if ($orderByClause->count()) {
            $criteria->orderBy($orderByClause);
        }

        // Build pagination restrictions
        $pagination = $this->buildPagination($originalData);

        // First result is originally indexed as 0-based
        $criteria->setFirstResult($pagination['firstResult'] - 1);
        $criteria->setMaxResults($pagination['maxResults']);

        return $criteria;
    }

    /**
     * Build Parameter list
     *
     * @param array $originalParameters
     * @param array $appendedParameters
     *
     * @return array
     */
    private function buildParameters($originalParameters, $appendedParameters)
    {
        $parameters = $originalParameters;

        // Adding newly added parameters
        foreach ($appendedParameters as $key => $value) {
            $parameter = new Parameter($key, $value);

            $parameters->set($key, $parameter);
        }

        return $parameters;
    }

    /**
     * Build pagination restrictions.
     *
     * @param array $data
     *
     * @return array
     */
    private function buildPagination(array $data)
    {
        // Calculate maximum amount of results
        $maxResults = self::MAX_RESULTS;

        if (isset($data['maxResults']) && $data['maxResults'] > 0 && $data['maxResults'] < $maxResults) {
            $maxResults = $data['maxResults'];
        }

        // Override the first result if the page exists
        if (isset($data['page'])) {
            $data['firstResult'] = ($data['page'] - 1) * $data['maxResults'] + 1;
        }

        // Calculate first result item
        $firstResult = (isset($data['firstResult']) && $data['firstResult'] > 0) ? $data['firstResult'] : 1;

        return array(
            'firstResult' => $firstResult,
            'maxResults'  => $maxResults
        );
    }
}
