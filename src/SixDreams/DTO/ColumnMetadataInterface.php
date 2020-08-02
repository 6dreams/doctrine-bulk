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
     * Returns column type.
     *
     * @return string
     */
    public function getType(): string;
}
