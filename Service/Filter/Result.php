<?php
/**
 * @copyright 2012 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Service\Filter;

use JMS\Serializer\Annotation as Rest;

/**
 * Filter Result
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 * @author John Cartwright <johnc@nationalfibre.net>
 */
class Result
{
    /**
     * @Rest\Type("integer")
     *
     * @var integer
     */
    private $totalPages;

    /**
     * @Rest\Type("integer")
     *
     * @var integer
     */
    private $currentPage;

    /**
     * @Rest\Type("integer")
     *
     * @var integer
     */
    private $totalResults;

    /**
     * @Rest\Type("integer")
     *
     * @var integer
     */
    private $firstResult;

    /**
     * @Rest\Type("integer")
     *
     * @var integer
     */
    private $maxResults;

    /**
     * @Rest\Type("array")
     *
     * @var array
     */
    private $resultList;

    /**
     * Retrieve the total pages.
     *
     * @return integer
     */
    public function getTotalPages()
    {
        return $this->totalPages;
    }

    /**
     * Define the total pages.
     *
     * @param integer $totalPages
     */
    public function setTotalPages($totalPages)
    {
        $this->totalPages = $totalPages;
    }

    /**
     * Retrieve the current page.
     *
     * @return integer
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * Define the current page.
     *
     * @param integer $currentPage
     */
    public function setCurrentPage($currentPage)
    {
        $this->currentPage = $currentPage;
    }

    /**
     * Retrieve the total results.
     *
     * @return integer
     */
    public function getTotalResults()
    {
        return $this->totalResults;
    }

    /**
     * Define the total results.
     *
     * @param integer $totalResults
     */
    public function setTotalResults($totalResults)
    {
        $this->totalResults = $totalResults;
    }

    /**
     * Retrieve the first result.
     *
     * @return integer
     */
    public function getFirstResult()
    {
        return $this->firstResult;
    }

    /**
     * Define the first result.
     *
     * @param integer $firstResult
     */
    public function setFirstResult($firstResult)
    {
        $this->firstResult = $firstResult;
    }

    /**
     * Retrieve the max results.
     *
     * @return integer
     */
    public function getMaxResults()
    {
        return $this->maxResults;
    }

    /**
     * Define the max results.
     *
     * @param integer $maxResults
     */
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;
    }

    /**
     * Retrieve the result list.
     *
     * @return array
     */
    public function getResultList()
    {
        return $this->resultList;
    }

    /**
     * Define the result list.
     *
     * @param array $resultList
     */
    public function setResultList($resultList)
    {
        $this->resultList = $resultList;
    }
}
