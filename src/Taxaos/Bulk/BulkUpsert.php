<?php
declare(strict_types=1);

namespace Taxaos\Bulk;

use Taxaos\Exceptions\FieldNotFoundException;
use Taxaos\Exceptions\NoDefaultValueException;
use Taxaos\Exceptions\NullValueException;
use Taxaos\Exceptions\WrongEntityException;

/**
 * Allows to insert multiple doctrine entities to database.
 */
class BulkUpsert extends AbstractBulk
{
    public const FLAG_NONE = 0;
    public const FLAG_IGNORE_MODE = 1 << 1;
    public const FLAG_IGNORE_DUPLICATES = 1 << 2;
    public const FLAG_NO_RETURN_ID = 1 << 3;

    public const DEFAULT_ROWS = 1000;

    /** @var array[] */
    private array $values = [];

    /**
     * Data.
     *
     * @param array $data
     *
     * @return BulkUpsert
     */
    public function addValue(array $data): BulkUpsert
    {
        foreach (array_keys($data) as $name) {
            if (!$this->metadata->hasField((string)$name)) {
                throw new FieldNotFoundException($this->class, $name);
            }
        }
        foreach ($this->metadata->getFields() as $field => $column) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!$column->isNullable() && !array_key_exists($field, $data)) {
                throw new NullValueException($this->class, $field);
            }
        }

        $this->values[] = $data;

        return $this;
    }

    /**
     * Adds entity to persist queue.
     *
     * @param object $entity
     *
     * @return BulkUpsert
     *
     * @throws NullValueException
     * @throws WrongEntityException
     */
    public function addEntity(object $entity): BulkUpsert
    {
        if (get_class($entity) !== $this->class) {
            throw new WrongEntityException($this->class, $entity);
        }

        $ret = [];
        foreach ($this->metadata->getFields() as $field => $column) {
            $classValue = $this->getJoinedEntityValue(
                $column,
                $this->getClassValue($this->reflection, $field, $entity),
                $field
            );

            if (!$classValue->isInitialised()) {
                if (!$column->hasDefault()) {
                    throw new NoDefaultValueException($this->class, $field);
                }

                $ret[$field] = $column->getDefault();
                continue;
            }

            $value = $classValue->getValue();
            if ($value === null && !$column->isNullable()) {
                throw new NullValueException($this->class, $field);
            }

            $ret[$field] = $value;
        }

        $generator = $this->metadata->getGenerator();
        $idFields = $this->metadata->getIdFields();

        foreach ($idFields as $idField) {
            if ($generator !== null && null === $ret[$idField] ?? null) {
                $ret[$idField] = $generator->generateBulk($this->manager, $this->class, $ret);
            }
        }

        $this->values[] = $ret;

        return $this;
    }

    /**
     * Executes insert to database and returns id of first inserted element.
     *
     * @param int $flags
     * @param int $maxRows
     *
     * @return string|null
     */
    public function execute(int $flags = self::FLAG_NONE, int $maxRows = self::DEFAULT_ROWS): ?string
    {
        if (!count($this->values)) {
            return null;
        }

        if ($flags & self::FLAG_IGNORE_DUPLICATES) {
            $temp = [];
            foreach ($this->values as $value) {
                $temp[implode('.', $value)] = $value;
            }

            $this->values = array_values($temp);
            unset($temp);
        }

        $lastId = null;
        foreach (array_chunk($this->values, $maxRows) as $values) {
            $lastInsertId = $this->executePartial($flags, $values);
            $lastId = $lastId ?? $lastInsertId;
        }
        $this->values = [];

        return $lastId;
    }

    /**
     * Executes insert to database and returns id of first inserted element.
     *
     * @param int $flags
     * @param array $values
     *
     * @return string|null
     */
    private function executePartial(int $flags, array $values): ?string
    {
        $fields = $this->getAllUsedFields($values);

        $query = sprintf(
            '%s INTO %s (%s) VALUES %s;',
            ($flags & self::FLAG_IGNORE_MODE) === self::FLAG_IGNORE_MODE ? 'INSERT IGNORE' : 'INSERT',
            $this->escape($this->metadata->getTable()),
            implode(', ', array_map(
                function (string $column) {
                    return $this->escape($this->metadata->getField($column)->getName());
                },
                $fields
            )),
            trim(str_repeat(sprintf('(%s), ', implode(', ', array_fill(0, count($fields), '?'))), count($values)), ', ')
        );

        $query = sprintf('%s ON DUPLICATE KEY UPDATE', $query);

        $stmt = $this->manager->getConnection()->prepare($query);
        $index = 0;
        foreach ($values as $row) {
            foreach ($fields as $name) {
                $index++;
                $value = $row[$name] ?? null;
                if (in_array($name, $this->metadata->getIdFields(), true) && ($generate = $this->metadata->getGenerator())) {
                    $value = $generate->generateBulk($this->manager, $this->class, $row);
                }
                $this->bind($stmt, $index, $this->metadata->getField($name), $value);
            }
        }

        $stmt->executeQuery();

        $noLastId = ($flags & self::FLAG_NO_RETURN_ID) === self::FLAG_NO_RETURN_ID || $this->metadata->getGenerator() !== null;

        return $noLastId ? null : $this->manager->getConnection()->lastInsertId();
    }
}