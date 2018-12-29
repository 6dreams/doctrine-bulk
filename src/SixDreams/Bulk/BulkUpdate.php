<?php
declare(strict_types = 1);

namespace SixDreams\Bulk;

use Doctrine\ORM\EntityManagerInterface;
use SixDreams\DTO\Metadata;
use SixDreams\Exceptions\CannotChangeWhereException;
use SixDreams\Exceptions\FieldNotFoundException;
use SixDreams\Exceptions\NullValueException;
use SixDreams\Exceptions\WrongEntityException;

/**
 * Allows to update multiple doctrine entities in database.
 */
class BulkUpdate extends AbstractBulk
{
    /** @var EntityManagerInterface */
    private $manager;

    /** @var string */
    private $class;

    /** @var Metadata */
    private $metadata;

    /** @var array[] */
    private $values = [];

    /** @var \ReflectionClass */
    private $reflection;
    
    /** @var string */
    private $whereField;

    /** @var \ReflectionProperty[] */
    private $cachedReflProps = [];

    /**
     * BulkUpdate constructor.
     *
     * @param EntityManagerInterface $manager
     * @param string                 $class   FCQN
     */
    public function __construct(EntityManagerInterface $manager, string $class)
    {
        $this->manager    = $manager;
        $this->class      = $class;
        $this->metadata   = MetadataLoader::load($manager->getClassMetadata($class));
        $this->reflection = new \ReflectionClass($class);
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
        foreach ($this->metadata->getFields() as $field => $column) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!$column->isNullable() && \array_key_exists($field, $data) && null === $data[$field]) {
                throw new NullValueException($this->class, $field);
            }
        }

        $this->values[$where] = $data;

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

        $fields = \array_flip($fields);

        $where = null;
        $data  = [];
        foreach ($this->metadata->getFields() as $field => $column) {
            if ($field === $this->whereField) {
                $where = $this->getClassProperty($this->reflection, $field)->getValue($entity);
                continue;
            }
            if ($fields && !\array_key_exists($field, $fields)) {
                continue;
            }
            $data[$field] = $this->getClassProperty($this->reflection, $field)->getValue($entity);
            if (null === $data[$field] && !$column->isNullable()) {
                throw new NullValueException($this->class, $field);
            }
        }

        if (\count($data)) {
            $this->values[$where] = $data;
        }

        return $this;
    }

    public function execute()
    {
        $values = $this->values;
        if (!\count($values)) {
            return null;
        }

        $fields = $this->getAllUsedFields($values);



        $cases = [];
        $thenId = 0;
        foreach ($values as $when => $entity) {
            //$cases[$field][] = \sprintf('CASE %s', $this->whereField);
            $when = $this->escapeValue($when);
            foreach ($fields as $field) {
                if (\array_key_exists($field, $entity)) {
                    $cases[$field][] = \sprintf(
                        'WHEN %s THEN %s',
                        $when ?? ':W' . $thenId,
                        $this->escapeValue($entity[$field]) ?? ':T' . $thenId
                    );
                } else {
                    $cases[$field][] = \sprintf('WHEN %s THEN %s', $when ?? ':W' . $thenId, $field); // todo: escape field
                }
                $thenId++;
            }
        }
        foreach ($cases as $field => &$case) {
            $case = \sprintf('SET %s = (%s)', $field, \implode(' ', $case));
        }
        unset($case);
        $cases = \implode(', ', $cases);

        $criterias = '';
        $critId    = 0;
        foreach (\array_keys($values) as $criteria) {
            if ('' !== $criterias) {
                $criterias .= ', ';
            }
            $criterias .= $this->escapeValue($criteria) ?? ':C' . $critId;
        }

        $query = \sprintf(
            'UPDATE %s %s WHERE %s IN (%s);',
            $this->metadata->getTable(),
            $cases,
            $this->whereField, // todo: escape where & table
            $criterias // todo: \implode(\array_keys($values)) with escape or binding..
        );
        /*
UPDATE table_name
SET text = (CASE id WHEN 1 THEN 'da'
                    WHEN 2 THEN 'net'
           END)
SET text2 = (CASE id WHEN 1 THEN 'net'
                    WHEN 2 THEN text2)
WHERE id IN (1, 2);

UPDATE %s SET field = (CASE field WHEN ?|value THEN ?|value|field ...) ... WHERE field IN (?|value)
         */
        // todo.
    }

    protected function escapeValue($value)
    {
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
