<?php
declare(strict_types = 1);

namespace SixDreams\DTO;

use Doctrine\ORM\Id\AbstractIdGenerator;
use SixDreams\Generator\BulkGeneratorInterface;

/**
 * Class MetadataDto
 */
final class Metadata
{
    /** @var string */
    private $table;

    /** @var AbstractColumnMetadata[] */
    private $fields = [];

    /** @var string */
    private $idField;

    /** @var BulkGeneratorInterface|null */
    private $generator;

    /**
     * MetadataDto constructor.
     *
     * @param string $table
     */
    public function __construct(?string $table = null)
    {
        $this->table = $table;
    }

    /**
     * Getter for Table
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Setter for Table.
     *
     * @param string $table
     *
     * @return Metadata
     */
    public function setTable(string $table): Metadata
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Add column.
     *
     * @param string                 $field
     * @param AbstractColumnMetadata $column
     *
     * @return Metadata
     */
    public function addField(string $field, AbstractColumnMetadata $column): Metadata
    {
        $this->fields[$field] = $column;

        return $this;
    }

    /**
     * Getter for Fields
     *
     * @return AbstractColumnMetadata[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Is table has field?
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasField(string $name): bool
    {
        return \array_key_exists($name, $this->fields);
    }

    /**
     * Get field by it's name. Risky!
     *
     * @param string $name
     *
     * @return AbstractColumnMetadata
     */
    public function getField(string $name): AbstractColumnMetadata
    {
        return $this->fields[$name];
    }

    /**
     * Getter for IdField
     *
     * @return string
     */
    public function getIdField(): string
    {
        return $this->idField;
    }

    /**
     * Setter for IdField.
     *
     * @param string $idField
     *
     * @return Metadata
     */
    public function setIdField(string $idField): Metadata
    {
        $this->idField = $idField;

        return $this;
    }

    /**
     * Getter for Generator
     *
     * @return BulkGeneratorInterface|null
     */
    public function getGenerator(): ?BulkGeneratorInterface
    {
        return $this->generator;
    }

    /**
     * Setter for Generator.
     *
     * @param BulkGeneratorInterface|null $generator
     *
     * @return Metadata
     */
    public function setGenerator(?BulkGeneratorInterface $generator): Metadata
    {
        $this->generator = $generator;

        return $this;
    }
}
