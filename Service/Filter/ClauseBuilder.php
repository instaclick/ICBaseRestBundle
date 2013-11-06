<?php
/**
 * @copyright 2012 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Service\Filter;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Criteria Clause Builder
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 */
abstract class ClauseBuilder
{
    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $classMetadata;

    /**
     * Constructor.
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadataInfo $classMetadata
     */
    public function __construct(ClassMetadataInfo $classMetadata)
    {
        $this->classMetadata = $classMetadata;
    }

    /**
     * Check if a given property is a filterable property.
     *
     * @param string $propertyName
     *
     * @return boolean
     */
    protected function checkValidProperty($propertyName)
    {
        if ($this->classMetadata->hasField($propertyName)) {
            return true;
        }

        return $this->classMetadata->hasAssociation($propertyName)
            && $this->classMetadata->isSingleValuedAssociation($propertyName);
    }
}
