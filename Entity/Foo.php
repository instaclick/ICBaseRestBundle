<?php
/**
 * @copyright 2012 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

use DMS\Filter\Rules as Filter;

use JMS\Serializer\Annotation as Rest;

use IC\Bundle\Base\ComponentBundle\Entity\Entity;

/**
 * Foo Entity for Testing
 *
 * @ORM\Entity(repositoryClass="IC\Bundle\Base\ComponentBundle\Entity\Repository\EntityRepository")
 * @ORM\Table(options={ "comment"="this table is for testing REST functionality" })
 *
 * @author Juti Noppornpitak <jutin@nationalfibre.net>
 * @author Oleksandr Kovalov <oleksandrk@nationalfibre.net>
 * @author Yuan Xie <shayx@nationalfibre.net>
 */
class Foo extends Entity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", options={ "comment"="primary key, autoincrement, counter" })
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Rest\Type("int")
     * @Rest\ReadOnly()
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=30, nullable=true, options={ "comment"="tested content" })
     *
     * @Assert\Length(max=30)
     *
     * @Rest\Type("string")
     *
     * @var string
     */
    private $content;

    /**
     * Get the ID.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Retrieve the content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Define the content.
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
}
