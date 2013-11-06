<?php
/**
 * @copyright 2012 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Service\Filter;

use Doctrine\Common\Util\Inflector;

use Doctrine\ORM\Query\Expr;

/**
 * Query Order By Clause Builder
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 * @author Juti Noppornpitak <jutin@nationalfibre.net>
 */
class OrderByClauseBuilder extends ClauseBuilder
{
    /**
     * Build the query order by clause.
     *
     * @param array $data
     *
     * @return \Doctrine\ORM\Query\Expr\OrderBy
     */
    public function build(array $data)
    {
        $orderBy = new Expr\OrderBy();

        foreach ($data as $propertyName => $sortOrder) {
            $orderByItem = $this->buildOrderByItem($propertyName, $sortOrder);

            $this->appendOrderByItem($orderBy, $orderByItem);
        }

        return $orderBy;
    }

    /**
     * Build an individual order by item.
     *
     * @param string $propertyName Property name
     * @param string $sortOrder    Sort order
     *
     * @return array
     */
    private function buildOrderByItem($propertyName, $sortOrder)
    {
        $propertyName = Inflector::camelize($propertyName);

        if ( ! $this->checkValidProperty($propertyName)) {
            return null;
        }

        if (empty($sortOrder) || ! in_array(strtoupper($sortOrder), array('DESC', 'ASC'))) {
            $sortOrder = 'ASC';
        }

        return array(
            'field' => "e.".$propertyName,
            'sort'  => $sortOrder
        );
    }

    /**
     * Append an order by item.
     *
     * @param \Doctrine\ORM\Query\Expr\OrderBy $orderBy     Order by
     * @param array                            $orderByItem Order by item
     */
    private function appendOrderByItem($orderBy, $orderByItem)
    {
        if ($orderByItem) {
            $orderBy->add($orderByItem['field'], $orderByItem['sort']);
        }
    }
}
