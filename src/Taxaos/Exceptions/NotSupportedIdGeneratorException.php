<?php
declare(strict_types=1);

namespace Taxaos\Exceptions;

use Taxaos\Generator\BulkGeneratorInterface;

/**
 * Class NotSupportedIdGeneratorException
 */
final class NotSupportedIdGeneratorException extends TaxaosException
{
    /**
     * NotSupportedIdGeneratorException constructor.
     *
     * @param object $name
     */
    public function __construct(object $name)
    {
        parent::__construct(
            sprintf(
                'To use generator "%s" in bulk please implement "%s" interface',
                get_class($name),
                BulkGeneratorInterface::class
            ));
    }
}
