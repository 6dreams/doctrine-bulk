<?php
declare(strict_types=1);

namespace SixDreams\DTO;

/**
 * Contains information about table column.
 */
abstract class AbstractColumnMetadata implements ColumnMetadataInterface
{
    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var bool */
    private $nullable;

    /** @var bool */
    private bool $hasDefault;

    /** @var mixed */
    private mixed $default;

    /**
     * ColumnMetadata constructor.
     *
     * @param string $name
     * @param string $type
     * @param bool $nullable
     * @param bool $hasDefault
     * @param mixed $default
     */
    public function __construct(string $name, string $type, bool $nullable, bool $hasDefault, mixed $default)
    {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
        $this->hasDefault = $hasDefault;
        $this->default = $default;
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
