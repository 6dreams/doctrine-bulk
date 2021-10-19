<?php
declare(strict_types=1);

namespace SixDreams\DTO;

/**
 * Contains information about table column.
 */
interface ColumnMetadataInterface
{
    /**
     * Returns column name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Return true, if column has active nullable flag.
     *
     * @return bool
     */
    public function isNullable(): bool;

    /**
     * Return true, if column has a default.
     *
     * @return bool
     */
    public function hasDefault(): bool;

    /**
     * Return the default value
     *
     * @return bool
     */
    public function getDefault(): mixed;

    /**
     * Returns column type.
     *
     * @return string
     */
    public function getType(): string;
}
