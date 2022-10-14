<?php
declare(strict_types=1);

namespace Taxaos\DTO;

/**
 * Contains information about table column.
 */
abstract class AbstractColumnMetadata implements ColumnMetadataInterface
{

    /**
     * ColumnMetadata constructor.
     *
     * @param string $name
     * @param string $type
     * @param bool $nullable
     * @param bool $hasDefault
     * @param mixed $default
     */
    public function __construct(private string $name, private string $type, private bool $nullable, private bool $hasDefault, private mixed $default)
    {
    }

    /**
     * Getter for Name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Getter for Nullable
     *
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * Getter for hasDefault
     * used for checking if the mapping has a default value
     *
     * @return bool
     */
    public function hasDefault(): bool
    {
        return $this->hasDefault;
    }

    /**
     * Getter for Default
     * should call 'hasDefault' first to check if the default returned is valid
     *
     * @return mixed
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * Getter for Type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
