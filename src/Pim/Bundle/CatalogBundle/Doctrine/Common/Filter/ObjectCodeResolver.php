<?php

namespace Pim\Bundle\CatalogBundle\Doctrine\Common\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;
use Pim\Component\Catalog\Exception\ObjectNotFoundException;
use Pim\Component\Catalog\Model\AttributeInterface;

/**
 * Object code resolver
 *
 * @author    Julien Janvier <j.janvier@gmail.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ObjectCodeResolver
{
    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var array */
    protected $fieldMapping = [];

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getCodesFromIds($entityName, array $ids, AttributeInterface $attribute = null)
    {
        if (!isset($this->fieldMapping[$entityName])) {
            throw new \InvalidArgumentException(sprintf('The class %s cannot be found', $entityName));
        }

        $repository = $this->managerRegistry
            ->getManagerForClass($this->fieldMapping[$entityName])
            ->getRepository($this->fieldMapping[$entityName]);

        $codes = [];
        foreach ($ids as $id) {
            $entity = $repository->findOneBy(['id' => $id]);

            if (!$entity) {
                throw new ObjectNotFoundException(
                    sprintf('Object "%s" with id "%s" does not exist', $entityName, $id)
                );
            }

            $code = $entity->getCode();
            if (null !== $attribute) {
                $code = $attribute->getCode() . '.' . $code;
            }

            $codes[] = $code;
        }

        return $codes;
    }

    /**
     * {@inheritdoc}
     */
    public function addFieldMapping($entityName, $className)
    {
        $this->fieldMapping[$entityName] = $className;
    }
}
