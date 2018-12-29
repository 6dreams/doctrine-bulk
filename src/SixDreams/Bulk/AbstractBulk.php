<?php
declare(strict_types = 1);

namespace SixDreams\Bulk;

use SixDreams\Exceptions\FieldNotFoundException;

/**
 * Class AbstractBulk
 */
abstract class AbstractBulk
{
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
}
