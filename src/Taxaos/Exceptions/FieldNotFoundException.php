<?php
declare(strict_types = 1);

namespace Taxaos\Exceptions;

/**
 * Class FieldNotFoundException
 */
final class FieldNotFoundException extends TaxaosException
{
    /**
     * FieldNotFoundException constructor.
     *
     * @param string $entity
     * @param string $field
     */
    public function __construct(string $entity, string $field)
    {
        parent::__construct(sprintf('Field "%s" not found in "%s"!', $field, $entity));
    }
}
