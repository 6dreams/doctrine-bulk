<?php
declare(strict_types = 1);

namespace SixDreams\Exceptions;

use SixDreams\Generator\BulkGeneratorInterface;

/**
 * Class NotSupportedIdGeneratorException
 */
final class NotSupportedIdGeneratorException extends SixDreamsException
{
    /**
     * NotSupportedIdGeneratorException constructor.
     *
     * @param object $name
     */
    public function __construct(object $name)
    {
        parent::__construct(\sprintf(
            'To use generator "%s" in bulk please implement "%s" interface',
            \get_class($name),
            BulkGeneratorInterface::class
        ));
    }
}
