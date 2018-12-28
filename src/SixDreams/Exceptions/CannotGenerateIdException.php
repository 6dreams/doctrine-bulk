<?php
declare(strict_types = 1);

namespace SixDreams\Exceptions;

/**
 * Class CannotGenerateIdException
 */
final class CannotGenerateIdException extends SixDreamsException
{
    /**
     * CannotGenerateIdException constructor.
     *
     * @param string $entity
     * @param string $field
     */
    public function __construct(string $entity, string $field)
    {
        parent::__construct(\sprintf(
            'Cannot generate Id for "%s" required field "%s" not exists!',
            $entity,
            $field
        ));
    }
}
