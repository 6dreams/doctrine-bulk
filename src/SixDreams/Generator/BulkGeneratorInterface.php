<?php
declare(strict_types = 1);

namespace SixDreams\Generator;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Interface that must be use in custom Doctrine generator, for Id generation in BulkInsert.
 */
interface BulkGeneratorInterface
{
    /**
     * Generate ID by entity data.
     *
     * @param EntityManagerInterface $manager
     * @param string                 $class
     * @param array                  $entity
     *
     * @return string
     */
    public function generateBulk(EntityManagerInterface $manager, string $class, array $entity): string;
}
