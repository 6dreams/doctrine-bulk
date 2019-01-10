<?php
declare(strict_types = 1);

namespace Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Setup;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractBulkTest
 */
abstract class AbstractBulkTest extends TestCase
{
    /**
     * Create new entity manager.
     *
     * @return EntityManagerInterface
     */
    protected function getManager(): EntityManagerInterface
    {
        return EntityManager::create(
            ['driver'   => 'pdo_sqlite'],
            Setup::createAnnotationMetadataConfiguration([__DIR__ . '/Entity/'], true, null, null, false)
        );
    }
}
