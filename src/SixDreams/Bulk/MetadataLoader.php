<?php
declare(strict_types = 1);

namespace SixDreams\Bulk;

use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use SixDreams\DTO\ColumnMetadata;
use SixDreams\DTO\JoinColumnMetadata;
use SixDreams\DTO\Metadata;
use SixDreams\Exceptions\NotSupportedIdGeneratorException;
use SixDreams\Generator\BulkGeneratorInterface;
use function array_filter;
use function array_key_exists;
use function count;

/**
 * Class MetadataLoader
 */
final class MetadataLoader
{
    /** @var Metadata[] */
    private static array $metadata = [];

    // Supported Join types.
    private const SUPPORTED_JOINS = [ClassMetadataInfo::ONE_TO_ONE => null, ClassMetadataInfo::MANY_TO_ONE => null];

    /**
     * MetadataLoader constructor.
     */
    private function __construct()
    {
    }

    /**
     * Loads metadata from "cache" or doctrine metadata.
     *
     * @param ClassMetadata $metadata
     *
     * @return Metadata
     */
    public static function load(ClassMetadata $metadata): Metadata
    {
        $class = $metadata->getName();
        if (array_key_exists($class, self::$metadata)) {
            return self::$metadata[$class];
        }
        $dmeta = new Metadata($metadata->getTableName());

        $id = $metadata->getSingleIdentifierFieldName();
        $dmeta->setIdField($id);

        foreach ($metadata->fieldMappings as $field => $mapping) {
            // if ->nullable() is not called doctrine does not include the 'nullable' key,
            // default to doctrines default of false, otherwise get the key
            $nullableKeyExists = array_key_exists('nullable', $mapping);
            $nullable = $field === $id ? true : ($nullableKeyExists && (bool) $mapping['nullable']);
            // ids are auto increment, so allow default
            $hasDefault = $field === $id ? true : (
            array_key_exists('options', $mapping) ? array_key_exists('default', $mapping['options']) : false
            );
            $defaultValue = $field === $id ? null : ($hasDefault ? $mapping['options']['default'] : null);
            $dmeta->addField($field, new ColumnMetadata($mapping['columnName'], $mapping['type'], $nullable, $hasDefault, $defaultValue));
        }

        $generator = $metadata->customGeneratorDefinition['class'] ?? null;
        if ($generator) {
            $generator = new $generator();
            if (!($generator instanceof BulkGeneratorInterface) || !($generator instanceof AbstractIdGenerator)) {
                throw new NotSupportedIdGeneratorException($generator);
            }
            $dmeta->setGenerator($generator);
        }

        $associations = array_filter($metadata->getAssociationMappings(), function (array $association) {
            return array_key_exists($association['type'], self::SUPPORTED_JOINS);
        });

        foreach ($associations as $association) {
            $column = $association['joinColumns'][0] ?? [];
            if (!count($column)) {
                continue; // looks broken...
            }
            // ONE_TO_ONE  does not have the 'nullable' key, but creates tables that are nullable
            $nullable = ($association['type'] === ClassMetadataInfo::ONE_TO_ONE || (bool) $column['nullable']);
            $hasDefault = false; // joins can never have a default relation
            $defaultValue = null;
            $dmeta->addField(
                $association['fieldName'],
                (new JoinColumnMetadata($column['name'], '', $nullable, $hasDefault, $defaultValue))
                    ->setReferenced($column['referencedColumnName'])
            );
        }

        self::$metadata[$class] = $dmeta;

        return $dmeta;
    }
}
