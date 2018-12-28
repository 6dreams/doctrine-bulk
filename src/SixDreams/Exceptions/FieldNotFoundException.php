<?php
declare(strict_types = 1);

namespace SixDreams\Exceptions;

/**
 * Class FieldNotFoundException
 */
final class FieldNotFoundException extends SixDreamsException
{
    /**
     * FieldNotFoundException constructor.
     *
     * @param string $entity
     * @param string $field
     */
    public function __construct(string $entity, string $field)
    {
        parent::__construct(\sprintf('Field "%s" not found in "%s"!', $field, $entity));
    }
}
