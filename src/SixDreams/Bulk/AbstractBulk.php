<?php
declare(strict_types = 1);

namespace SixDreams\Bulk;

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use SixDreams\DTO\ColumnMetadataInterface;
use SixDreams\DTO\JoinColumnMetadata;
use SixDreams\DTO\Metadata;
use SixDreams\Exceptions\FieldNotFoundException;

/**
 * Class AbstractBulk
 */
abstract class AbstractBulk
{
    /** @var EntityManagerInterface */
    protected $manager;

    /** @var \ReflectionProperty[] */
    private $cachedReflProps = [];

    /** @var \ReflectionClass */
    protected $reflection;

    /** @var string */
    protected $class;

    /** @var Metadata */
    protected $metadata;

    /**
     * BulkQuery constructor.
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
     * Search property in class or it's subclasses and make it accessible.
     *
     * @param \ReflectionClass $class
     * @param string           $name
     *
     * @return \ReflectionProperty
     *
     * @throws FieldNotFoundException
     */
    protected function getClassProperty(\ReflectionClass $class, string $name): \ReflectionProperty
    {
        if ($class->hasProperty($name)) {
            $property = $class->getProperty($name);
            $property->setAccessible(true);

            return $property;
        }

        $subClass = $class->getParentClass();
        if (!$class) {
            throw new FieldNotFoundException($class->getName(), $name);
        }

        return $this->getClassProperty($subClass, $name);
    }

    /**
     * Get the value of a property from a class or subclass.
     *
     * @param \ReflectionClass $class
     * @param string           $name
     * @param mixed            $object
     *
     * @return ClassValue
     *
     * @throws FieldNotFoundException
     */
    protected function getClassValue(\ReflectionClass $class, string $name, $object): ClassValue
    {
        // Embeded properties are in dot notaion
        if (str_contains($name, '.'))
        {
            $parts = explode('.', $name);
            $classValue = $this->getClassValue($class, $parts[0], $object);
            if (!$classValue->isInitialised())
            {
                return $classValue;
            }
            for ($i = 1; $i < count($parts); ++$i)
            {
                $oldValue = $classValue->value();
                $classValue = $this->getClassValue(new \ReflectionClass($oldValue), $parts[$i], $oldValue);
                if (!$classValue->isInitialised())
                {
                    return $classValue;
                }
            }

            return $classValue;
        }

        if ($class->hasProperty($name))
        {
            $property = $class->getProperty($name);
            $property->setAccessible(true);
            if (!$property->isInitialized($object))
            {
                return ClassValue::notInitialised();
            }

            return ClassValue::initialised($property->getValue($object));
        }

        $subClass = $class->getParentClass();
        if (!$class) {
            throw new FieldNotFoundException($class->getName(), $name);
        }

        return $this->getClassValue($subClass, $name, $object);
    }

    /**
     * Get all fields used in request.
     *
     * @param array $values
     *
     * @return array
     */
    protected function getAllUsedFields(array &$values): array
    {
        $fields = [[]];
        foreach ($values as $value) {
            $fields[] = \array_keys($value);
        }

        return \array_flip(\array_flip(\array_merge(...$fields)));
    }

    /**
     * Bind value to statement.
     *
     * @param Statement               $statement
     * @param int|string              $index
     * @param ColumnMetadataInterface $column
     * @param mixed                   $value
     */
    protected function bind(Statement $statement, $index, ColumnMetadataInterface $column, $value): void
    {
        $type = \PDO::PARAM_STR;
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

    /**
     * Extract joined entity value, if entity is really joined.
     *
     * @param ColumnMetadataInterface $column
     * @param ClassValue              $classValue
     * @param string                  $field
     *
     * @return ClassValue
     *
     * @throws FieldNotFoundException
     */
    protected function getJoinedEntityValue(ColumnMetadataInterface $column, ClassValue $classValue, string $field) : ClassValue
    {
        if (!$classValue->isInitialised())
        {
            return $classValue;
        }

        $value = $classValue->value();

        if (!($column instanceof JoinColumnMetadata) || $value === null || !is_object($value))
        {
            return $classValue;
        }

        $subPropName = $field . '.' . $column->getReferenced();
        if (!\array_key_exists($subPropName, $this->cachedReflProps))
        {
            $this->cachedReflProps[$subPropName] = $this->getClassProperty(
                new \ReflectionClass($value),
                $column->getReferenced()
            );
            $this->cachedReflProps[$subPropName]->setAccessible(true);
        }

        $prop = $this->cachedReflProps[$subPropName];
        if (!$prop->isInitialized($value))
        {
            return ClassValue::notInitialised();
        }

        return ClassValue::initialised($prop->getValue($value));
    }

    /**
     * Escape field or table.
     *
     * @param string $name
     *
     * @return string
     */
    protected function escape(string $name): string
    {
        return (new Identifier($name))->getQuotedName($this->manager->getConnection()->getDatabasePlatform());
    }
}
