<?php
declare(strict_types = 1);

namespace SixDreams\Bulk;

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use SixDreams\DTO\AbstractColumnMetadata;
use SixDreams\DTO\JoinColumnMetadata;
use SixDreams\DTO\Metadata;
use SixDreams\Exceptions\FieldNotFoundException;
use SixDreams\Exceptions\NullValueException;
use SixDreams\Exceptions\WrongEntityException;

/**
 * Class BulkInsert
 */
class BulkInsert
{
    public const FLAG_NONE              = 0;
    public const FLAG_IGNORE_MODE       = 1 << 1;
    public const FLAG_IGNORE_DUPLICATES = 1 << 2;
    public const FLAG_NO_RETURN_ID      = 1 << 3;

    public const DEFAULT_ROWS = 1000;

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

    /** @var \ReflectionProperty[] */
    private $cachedReflProps = [];

    /**
     * BulkInsert constructor.
     *
     * @param EntityManagerInterface $manager
     * @param string                 $class
     */
    public function __construct(EntityManagerInterface $manager, string $class)
    {
        $this->manager    = $manager;
        $this->class      = $class;
        $this->metadata   = MetadataLoader::load($manager->getClassMetadata($class));
        $this->reflection = new \ReflectionClass($class);
    }

    /**
     * Data.
     *
     * @param array $data
     *
     * @return BulkInsert
     */
    public function addValue(array $data): BulkInsert
    {
        foreach (\array_keys($data) as $name) {
            if (!$this->metadata->hasField((string) $name)) {
                throw new FieldNotFoundException($this->class, $name);
            }
        }
        foreach ($this->metadata->getFields() as $field => $column) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!$column->isNullable() && !\array_key_exists($field, $data)) {
                throw new NullValueException($field, $this->class);
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
     * @return BulkInsert
     *
     * @throws NullValueException
     * @throws WrongEntityException
     */
    public function addEntity(object $entity): BulkInsert
    {
        if (\get_class($entity) !== $this->class) {
            throw new WrongEntityException($this->class, $entity);
        }

        $ret = [];
        foreach ($this->metadata->getFields() as $field => $column) {
            $prop  = $this->reflection->getProperty($field);
            $prop->setAccessible(true);
            $value = $prop->getValue($entity);

            if (($column instanceof JoinColumnMetadata) && null !== $value) {
                $subPropName = $field . '.' . $column->getReferenced();
                if (!\array_key_exists($subPropName, $this->cachedReflProps)) {
                    $this->cachedReflProps[$subPropName] = (new \ReflectionClass($value))
                        ->getProperty($column->getReferenced());
                    $this->cachedReflProps[$subPropName]->setAccessible(true);
                }

                $value = $this->cachedReflProps[$subPropName]->getValue($value);
            }

            if (null === $value && !$column->isNullable()) {
                throw new NullValueException($field, $this->class);
            }

            $ret[$field] = $value;
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
        if ($flags & self::FLAG_IGNORE_DUPLICATES) {
            $temp = [];
            foreach ($this->values as $value) {
                $temp[\implode('.', $value)] = $value;
            }

            $this->values = \array_values($temp);
            unset($temp);
        }

        $lastId = null;
        foreach (\array_chunk($this->values, $maxRows) as $values) {
            $lastInsertId = $this->executePartial($flags, $values);
            $lastId = $lastId ?? $lastInsertId;
        }

        return $lastId;
    }

    /**
     * Executes insert to database and returns id of first inserted element.
     *
     * @param int   $flags
     * @param array $values
     *
     * @return string|null
     */
    private function executePartial(int $flags, array $values): ?string
    {
        $fields = [ [] ];
        foreach ($values as $value) {
            $fields[] = \array_keys($value);
        }
        $fields = \array_flip(\array_flip(\array_merge(...$fields)));

        $platform = $this->manager->getConnection()->getDatabasePlatform();

        $query = \sprintf(
            '%s INTO %s (%s) VALUES %s;',
            ($flags & self::FLAG_IGNORE_MODE) === self::FLAG_IGNORE_MODE ? 'INSERT IGNORE' : 'INSERT',
            (new Identifier($this->metadata->getTable()))->getQuotedName($platform),
            \implode(', ', \array_map(
                function (string $column) use ($platform) {
                    return (new Identifier($column))->getQuotedName($platform);
                },
                $fields
            )),
            \trim(\str_repeat(\sprintf('(%s), ', \implode(', ', \array_fill(0, \count($fields), '?'))), \count($values)), ', ')
        );

        $stmt = $this->manager->getConnection()->prepare($query);
        $index = 0;
        foreach ($values as $row) {
            foreach ($fields as $name) {
                $index++;
                $value = $row[$name] ?? null;
                if ($this->metadata->getIdField() === $name && ($generate = $this->metadata->getGenerator())) {
                    $value = $generate->generateBulk($this->manager, $this->class, $row);
                }
                $this->bind($stmt, $index, $this->metadata->getField($name), $value);
            }
        }

        $stmt->execute();

        return ($flags & self::FLAG_NO_RETURN_ID) === self::FLAG_NO_RETURN_ID ? null : $this->manager->getConnection()->lastInsertId();
    }

    /**
     * Bind value to statement.
     *
     * @param Statement              $statement
     * @param int                    $index
     * @param AbstractColumnMetadata $column
     * @param mixed                  $value
     */
    private function bind(Statement $statement, int $index, AbstractColumnMetadata $column, $value): void
    {
        $type = ParameterType::STRING;
        if (Type::hasType($column->getType())) {
            $type  = Type::getType($column->getType());
            $value = $type->convertToDatabaseValue(
                $value,
                $this->manager->getConnection()->getDatabasePlatform()
            );
            $type = $type->getBindingType();
        }

        $statement->bindValue($index, $value, $type);
    }
}
