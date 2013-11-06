<?php
/**
 * @copyright 2012 Instaclick Inc.
 */

namespace IC\Bundle\Base\RestBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator;
use DMS\Bundle\FilterBundle\Service\Filter as DMSFilter;
use JMS\Serializer\SerializerInterface;
use IC\Bundle\Base\ComponentBundle\Entity\Repository\EntityRepository;

/**
 * Mediator Service
 *
 * @author Guilherme Blanco <gblanco@nationalfibre.net>
 */
class MediatorService
{
    /**
     * @var \JMS\Serializer\SerializerInterface
     */
    private $serializerService;

    /**
     * @var \DMS\Bundle\FilterBundle\Service\Filter
     */
    private $filterService;

    /**
     * @var \Symfony\Component\Validator\Validator
     */
    private $validatorService;

    /**
     * {@inheritdoc}
     */
    public function setSerializerService(SerializerInterface $serializerService)
    {
        $this->serializerService = $serializerService;
    }

    /**
     * {@inheritdoc}
     */
    public function setFilterService(DMSFilter $filterService)
    {
        $this->filterService = $filterService;
    }

    /**
     * {@inheritdoc}
     */
    public function setValidatorService(Validator $validatorService)
    {
        $this->validatorService = $validatorService;
    }

    /**
     * Mediate the request
     *
     * @param \Symfony\Component\HttpFoundation\Request                          $request          Request
     * @param \IC\Bundle\Base\ComponentBundle\Entity\Repository\EntityRepository $entityRepository Entity repository
     *
     * @return mixed
     */
    public function mediate(Request $request, EntityRepository $entityRepository)
    {
        try {
            // Deserialize Entity
            $entity = $this->serializerService->deserialize(
                $request->getContent(),
                $entityRepository->getClassName(),
                $request->getRequestFormat()
            );
        } catch (\Exception $exception) {
            return $this->handleDeserializationException($exception);
        }

        // Filter Entity
        $this->filterService->filterEntity($entity);

        // Validate Entity
        $constraintViolationList = $this->validatorService->validate($entity);

        if ($constraintViolationList->count()) {
            return array(
                'message'   => 'Entity is not valid.',
                'code'      => 400,
                'validator' => $constraintViolationList
            );
        }

        return $entity;
    }

    /**
     * Handle deserialization exception
     *
     * @param \Exception $exception
     *
     * @return array
     */
    private function handleDeserializationException(\Exception $exception)
    {
        $return = array(
            'message'  => 'Unable to build entity.',
            'code'     => 400,
            'previous' => array(
                'message'  => $exception->getMessage(),
                'code'     => $exception->getCode()
            )
        );

        if ($exception->getPrevious()) {
            $return['previous']['previous'] = (string) $exception->getPrevious();
        }

        return $return;
    }
}
