<?php
declare(strict_types = 1);

namespace SixDreams\Bulk;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use SixDreams\DTO\ColumnMetadata;
use SixDreams\DTO\ColumnMetadataInterface;
use SixDreams\Exceptions\CannotChangeWhereException;
use SixDreams\Exceptions\FieldNotFoundException;
use SixDreams\Exceptions\NullValueException;
use SixDreams\Exceptions\WrongEntityException;

/**
 * Allows to update multiple doctrine entities in database.
 */
class BulkUpdate extends AbstractBulk
{
    /** @var array[] */
    private $values = [];

    /** @var string */
    private $whereField;

    /**
     * BulkUpdate constructor.
     *
     * @param EntityManagerInterface $manager
     * @param string                 $class   FCQN
     */
    public function __construct(EntityManagerInterface $manager, string $class)
    {
        parent::__construct($manager, $class);
        $this->whereField = $this->metadata->getIdField();
    }

    /**
     * Setter for WhereField.
     *
     * @param string $whereField
     *
     * @return BulkUpdate
     *
     * @throws CannotChangeWhereException
     */
    public function setWhereField(string $whereField): BulkUpdate
    {
        if (\count($this->values)) {
            throw new CannotChangeWhereException($this->class, $this->whereField, $whereField);
        }

        $this->whereField = $whereField;

        return $this;
    }



    /**
     * Adds single row to update queue.
     *
     * @param string|int $where
     * @param array      $data
     *
     * @return BulkUpdate
     *
     * @throws FieldNotFoundException
     * @throws NullValueException
     */
    public function addValue($where, array $data): BulkUpdate
    {
        foreach (\array_keys($data) as $name) {
            if (!$this->metadata->hasField((string) $name)) {
                throw new FieldNotFoundException($this->class, $name);
            }
        }
        $update = [];
        foreach ($this->metadata->getFields() as $field => $column) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!$column->isNullable() && \array_key_exists($field, $data) && null === $data[$field]) {
                throw new NullValueException($this->class, $field);
            }
            if (\array_key_exists($field, $data)) {
                $update[$column->getName()] = [$data[$field], $column];
            }
        }

        $this->values[$where] = $update;

        return $this;
    }

    /**
     * Adds entity to update queue. Using this method recommended with defined fields to update.
     *
     * @param object     $entity
     * @param array|null $fields
     *
     * @return BulkUpdate
     *
     * @throws WrongEntityException
     */
    public function addEntity(object $entity, ?array $fields = null): BulkUpdate
    {
        if (\get_class($entity) !== $this->class) {
            throw new WrongEntityException($this->class, $entity);
        }

        $fields = $fields ? \array_flip($fields) : null;

        $where = null;
        $data = $flat = [];
        foreach ($this->metadata->getFields() as $field => $column) {
            if ($field === $this->whereField) {
                $where = $this->getClassProperty($this->reflection, $field)->getValue($entity);
                continue;
            }
            if ($fields && !\array_key_exists($field, $fields)) {
                continue;
            }
            $value = $this->getJoinedEntityValue(
                $column,
                $this->getClassProperty($this->reflection, $field)->getValue($entity),
                $field
            );
            $data[$column->getName()] = [$value, $column];
            $flat[$column->getName()] = $value;
            if (null === $value && !$column->isNullable()) {
                throw new NullValueException($this->class, $field);
            }
        }

        if (\count($data)) {
            $generator = $this->metadata->getGenerator();
            if ($generator && null === $where) {
                $where = $generator->generateBulk($this->manager, $this->class, $flat);
            }

            $this->values[$where] = $data;
        }

        return $this;
    }

    /**
     * Execute update query. Return amount of affected rows.
     *
     * @return int
     */
    public function execute(): int
    {
        /** @var array $bindings */
        [$query, $bindings] = $this->getSQL();

        if (!$query) {
            return 0;
        }

        $stmt = $this->manager->getConnection()->prepare($query);
        foreach ($bindings as $name => $binding) {
            $this->bind($stmt, $name, $binding[1], $binding[0]);
        }

        $stmt->execute();

        return (int) $stmt->rowCount();
    }

    /**
     * Return SQL query and bindings.
     *
     * @return array
     */
    public function getSQL(): array
    {
        $values = $this->values;
        $platform = $this->manager->getConnection()->getDatabasePlatform();
        if (!\count($values)) {
            return [null, []];
        }

        $fields = $this->getAllUsedFields($values);
        $idMeta = $this->metadata->getField($this->metadata->getIdField());

        $cases = $bindings = [];
        $thenId = 0;
        foreach ($values as $when => $entity) {
            $whenEsc = $this->simpleValue($when, $platform);
            foreach ($fields as $field) {
                if (null === $whenEsc) {
                    $bindings[':W' . $thenId] = [$when, $idMeta];
                }
                if (\array_key_exists($field, $entity)) {
                    $cases[$field][] = \sprintf(
                        'WHEN %s = %s THEN %s',
                        $this->escape($idMeta->getName()),
                        $whenEsc ?? ':W' . $thenId,
                        $this->simpleValue($entity[$field][0], $platform, $entity[$field][1]) ?? ':T' . $thenId
                    );
                    if ($this->simpleValue($entity[$field][0], $platform, $entity[$field][1]) === null) {
                        $bindings[':T' . $thenId] = $entity[$field];
                    }
                } else {
                    $cases[$field][] = \sprintf(
                        'WHEN %s = %s THEN %s',
                        $this->escape($idMeta->getName()),
                        $whenEsc ?? ':W' . $thenId,
                        $this->escape($field)
                    );
                }
                $thenId++;
            }
        }
        foreach ($cases as $field => &$case) {
            $case = \sprintf('%s = CASE %s END', $this->escape($field), \implode(' ', $case));
        }
        unset($case);
        $cases = \implode(', ', $cases);

        $criterias = '';
        $critId    = 0;
        foreach (\array_keys($values) as $criteria) {
            if ('' !== $criterias) {
                $criterias .= ', ';
            }
            $critEsc    = $this->simpleValue($criteria, $platform);
            $criterias .= $critEsc ?? ':C' . $critId;
            if (null === $critEsc) {
                $bindings[':C' . $critId] = [$criteria, $idMeta];
            }
            $critId++;
        }

        $query = \sprintf(
            'UPDATE %s SET %s WHERE %s IN (%s);',
            $this->escape($this->metadata->getTable()),
            $cases,
            $this->escape($this->whereField),
            $criterias
        );

        return [$query, $bindings];
    }

    /**
     * Check is value is simple (float, int, null) and return it's representation in SQL, otherwise return null (marker
     *  that value require binding).
     *
     * @param mixed                        $value
     * @param AbstractPlatform             $platform
     * @param ColumnMetadataInterface|null $metadata
     *
     * @return float|int|string|null
     */
    protected function simpleValue($value, AbstractPlatform $platform, ?ColumnMetadataInterface $metadata = null)
    {
        if ($metadata && $platform->getName() === 'postgresql' && $metadata->getType() === Type::BOOLEAN) {
            return $value ? 'true' : 'false';
        }
        if (null === $value) {
            return 'NULL';
        }
        if (is_numeric($value)) {
            if (\strpos((string) $value, '.') !== false || \strpos((string) $value, ',') !== false) {
                return (float) $value;
            }

            return (int) $value;
        }

        return null;
    }
}
