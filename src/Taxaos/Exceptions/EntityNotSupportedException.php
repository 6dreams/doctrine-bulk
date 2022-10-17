<?php
declare(strict_types=1);

namespace Taxaos\Exceptions;

use Taxaos\Generator\HashedIdEntityInterface;
use Taxaos\Generator\HashedIdGenerator;

/**
 * Class EntityNotSupportedException
 */
final class EntityNotSupportedException extends TaxaosException
{
    /**
     * EntityNotSupportedException constructor.
     *
     * @param object $entity
     */
    public function __construct(object $entity)
    {
        parent::__construct(
            sprintf(
                'Entity with class "%s" must implement "%s" for used in "%s".',
                get_class($entity),
                HashedIdEntityInterface::class,
                HashedIdGenerator::class
            ));
    }
}
