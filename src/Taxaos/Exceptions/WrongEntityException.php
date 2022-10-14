<?php
declare(strict_types=1);

namespace Taxaos\Exceptions;

/**
 * Class WrongEntityException
 */
final class WrongEntityException extends TaxaosException
{
    /**
     * WrongEntityException constructor.
     *
     * @param string $excepted
     * @param object $actual
     */
    public function __construct(string $excepted, object $actual)
    {
        parent::__construct(
            sprintf(
                'Bulk class created for "%s", but "%s" added.',
                $excepted,
                get_class($actual)
            ));
    }
}
