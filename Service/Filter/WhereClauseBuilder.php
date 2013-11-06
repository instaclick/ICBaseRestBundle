<?php
/**
 * @copyright 2012 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Service\Filter;

use Doctrine\Common\Util\Inflector;

use Doctrine\ORM\Query\Expr;

/**
 * Query Where Clause Builder
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 * @author John Cartwright <johnc@nationalfibre.net>
 * @author Juti Noppornpitak <jutin@nationalfibre.net>
 */
class WhereClauseBuilder extends ClauseBuilder
{
    /**
     * Search wildcard character.
     */
    const WILDCARD_CHAR = '*';

    /**
     * Build the query where clause.
     *
     * @param array &$data Reference of parameter list information
     *
     * @return \Doctrine\ORM\Query\Expr\Andx
     */
    public function build(array &$data)
    {
        $andX = new Expr\Andx();

        foreach ($data as $propertyName => &$propertyValue) {
            $condition = $this->buildComparison($propertyName, $propertyValue);

            // If the condition is a string, assume that this is a isNull expression.
            if (is_string($condition)) {
                unset($data[$propertyName]);
            }

            // Modify the property value from WILDCARD_CHAR to % if property is a string
            $propertyValue = is_string($propertyValue)
                ? str_replace(self::WILDCARD_CHAR, '%', $propertyValue)
                : $propertyValue;

            $andX->add($condition);
        }

        return $andX;
    }

    /**
     * Build an individual where comparison.
     *
     * @param string $propertyName  Name of the property
     * @param mixed  $propertyValue Value of the property
     *
     * @return null|\Doctrine\ORM\Query\Expr\Comparison
     */
    private function buildComparison($propertyName, $propertyValue)
    {
        $originalPropertyName = $propertyName;
        $propertyName         = Inflector::camelize($propertyName);

        if ( ! $this->checkValidProperty($propertyName)) {
            return null;
        }

        $leftExpression  = 'e.' . $propertyName;
        $rightExpression = ':' . $originalPropertyName;

        switch (true) {
            case (is_array($propertyValue)):
                return new Expr\Func($leftExpression . ' IN', $rightExpression);
            case (strtolower($propertyValue) === 'null'):
                return sprintf('%s IS NULL', $leftExpression);
            case (strpos($propertyValue, self::WILDCARD_CHAR) !== false):
                return new Expr\Comparison($leftExpression, 'LIKE', $rightExpression);
            default:
                return new Expr\Comparison($leftExpression, Expr\Comparison::EQ, $rightExpression);
        }
    }
}
