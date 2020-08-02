<?php
declare(strict_types = 1);

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

    /**
     * ColumnMetadata constructor.
     *
     * @param string $name
     * @param string $type
     * @param bool   $nullable
     */
    public function __construct(string $name, string $type, bool $nullable = true)
    {
        $this->name     = $name;
        $this->type     = $type;
        $this->nullable = $nullable;
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
     * Getter for Type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
