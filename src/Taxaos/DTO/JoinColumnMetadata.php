<?php
declare(strict_types = 1);

namespace Taxaos\DTO;

/**
 * Class JoinColumnMetadata
 */
final class JoinColumnMetadata extends AbstractColumnMetadata
{
    private string $referenced;

    /**
     * Setter for Referenced.
     *
     * @param string $referenced
     *
     * @return JoinColumnMetadata
     */
    public function setReferenced(string $referenced): JoinColumnMetadata
    {
        $this->referenced = $referenced;

        return $this;
    }

    /**
     * Getter for Referenced
     *
     * @return string
     */
    public function getReferenced(): string
    {
        return $this->referenced;
    }
}
