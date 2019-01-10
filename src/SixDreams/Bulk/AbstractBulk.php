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
     * @param Statement              $statement
     * @param int|string             $index
     * @param AbstractColumnMetadata $column
     * @param mixed                  $value
     */
    protected function bind(Statement $statement, $index, AbstractColumnMetadata $column, $value): void
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

    /**
     * Extract joined entity value, if entity is really joined.
     *
     * @param AbstractColumnMetadata $column
     * @param object|null            $value
     * @param string                 $field
     *
     * @return mixed
     *
     * @throws FieldNotFoundException
     */
    protected function getJoinedEntityValue(AbstractColumnMetadata $column, $value, string $field)
    {
        if (($column instanceof JoinColumnMetadata) && null !== $value && \is_object($value)) {
            $subPropName = $field . '.' . $column->getReferenced();
            if (!\array_key_exists($subPropName, $this->cachedReflProps)) {
                $this->cachedReflProps[$subPropName] = $this->getClassProperty(
                    new \ReflectionClass($value),
                    $column->getReferenced()
                );
                $this->cachedReflProps[$subPropName]->setAccessible(true);
            }

            $value = $this->cachedReflProps[$subPropName]->getValue($value);
        }

        return $value;
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
