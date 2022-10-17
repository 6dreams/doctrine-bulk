<?php
declare(strict_types=1);

namespace Taxaos\Generator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use InvalidArgumentException;
use Taxaos\Exceptions\CannotGenerateIdException;
use Taxaos\Exceptions\EntityNotSupportedException;

/**
 * Class HashedIdGenerator
 */
class HashedIdGenerator extends AbstractIdGenerator implements BulkGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public function generate(EntityManager $em, $entity): string
    {
        if ($entity === null) {
            throw new InvalidArgumentException('Entity should not be null');
        }
        if (!($entity instanceof HashedIdEntityInterface)) {
            throw new EntityNotSupportedException($entity);
        }

        return SimpleHash::create($entity->getHashGeneratorValues());
    }

    /**
     * @inheritdoc
     */
    public function generateBulk(EntityManagerInterface $manager, string $class, array $entity): string
    {
        $object = new $class();
        if (!($object instanceof HashedIdEntityInterface)) {
            throw new EntityNotSupportedException($object);
        }

        $hash = [];
        foreach ($object->getHashGeneratorFields() as $field) {
            if (!\array_key_exists($field, $entity)) {
                throw new CannotGenerateIdException($class, $field);
            }

            $hash[] = $object->getHashGeneratorFieldValue($field, $entity[$field]);
        }

        return SimpleHash::create($hash);
    }
}
