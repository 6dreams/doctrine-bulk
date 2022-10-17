<?php
declare(strict_types=1);

namespace Taxaos\DTO;

/**
 * Contains information about table column.
 */
abstract class AbstractColumnMetadata implements ColumnMetadataInterface
{
    public function __construct(
        private string  $name,
        private string  $type,
        private bool    $nullable,
        private bool    $hasDefault,
        private mixed   $default
    )
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function hasDefault(): bool
    {
        return $this->hasDefault;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
