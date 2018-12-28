<?php
declare(strict_types = 1);

namespace SixDreams\Exceptions;

use SixDreams\Generator\HashedIdEntityInterface;
use SixDreams\Generator\HashedIdGenerator;

/**
 * Class EntityNotSupportedException
 */
final class EntityNotSupportedException extends SixDreamsException
{
    /**
     * EntityNotSupportedException constructor.
     *
     * @param object $entity
     */
    public function __construct(object $entity)
    {
        parent::__construct(\sprintf(
            'Entity with class "%s" must implement "%s" for used in "%s".',
            \get_class($entity),
            HashedIdEntityInterface::class,
            HashedIdGenerator::class
        ));
    }
}
