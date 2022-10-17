<?php
declare(strict_types = 1);

namespace Taxaos\Exceptions;

/**
 * Class NullValueException
 */
final class NullValueException extends TaxaosException
{
    /**
     * NullValueException constructor.
     *
     * @param string $name
     * @param string $class
     */
    public function __construct(string $name, string $class)
    {
        parent::__construct(sprintf('Null does not allow in field "%s" of "%s"', $name, $class));
    }
}
