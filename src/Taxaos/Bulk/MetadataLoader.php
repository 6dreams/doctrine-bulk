<?php
declare(strict_types=1);

namespace Taxaos\Bulk;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Taxaos\DTO\ColumnMetadata;
use Taxaos\DTO\JoinColumnMetadata;
use Taxaos\DTO\Metadata;
use Taxaos\Exceptions\NotSupportedIdGeneratorException;
use Taxaos\Generator\BulkGeneratorInterface;

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

        $bulkMetadata = new Metadata($metadata->getTableName());

        $ids = $metadata->getIdentifierFieldNames();
        $bulkMetadata->setIdFields($ids);
        $bulkMetadata->setLifeCycleCallBacks($metadata->lifecycleCallbacks);

        foreach ($metadata->fieldMappings as $field => $mapping) {
            // if ->nullable() is not called doctrine does not include the 'nullable' key,
            // default to doctrines default of false, otherwise get the key
            $isIdField = in_array($field, $ids, true);
            $nullableKeyExists = array_key_exists('nullable', $mapping);
            $nullable = $isIdField || ($nullableKeyExists && (bool)$mapping['nullable']);
            // ids are auto increment, so allow default
            $hasDefault = $isIdField || (
                    array_key_exists('options', $mapping) && array_key_exists('default', $mapping['options'])
                );

            $defaultValue = null;
            if (!$isIdField && $hasDefault) {
                $defaultValue = $mapping['options']['default'];
            }
            $bulkMetadata->addField(
                $field,
                new ColumnMetadata(
                    $mapping['columnName'],
                    $mapping['type'],
                    $nullable,
                    $hasDefault,
                    $defaultValue)
            );
        }

        $generator = $metadata->customGeneratorDefinition['class'] ?? null;
        if ($generator) {
            $generator = new $generator();
            if (!($generator instanceof BulkGeneratorInterface)) {
                throw new NotSupportedIdGeneratorException($generator);
            }
            $bulkMetadata->setGenerator($generator);
        }

        $associations = array_filter($metadata->getAssociationMappings(), static function (array $association) {
            return array_key_exists($association['type'], self::SUPPORTED_JOINS);
        });

        foreach ($associations as $association) {
            $column = $association['joinColumns'][0] ?? [];
            if (!count($column)) {
                continue; // looks broken...
            }
            // ONE_TO_ONE  does not have the 'nullable' key, but creates tables that are nullable
            $nullable = $association['type'] === ClassMetadataInfo::ONE_TO_ONE || $column['nullable'];
            $defaultValue = null;
            $joinColumnMetadata = new JoinColumnMetadata(
                $column['name'],
                '',
                $nullable,
                false, // joins can never have a default relation
                $defaultValue
            );

            $bulkMetadata->addField(
                $association['fieldName'],
                $joinColumnMetadata->setReferenced($column['referencedColumnName'])
            );
        }

        self::$metadata[$class] = $bulkMetadata;

        return $bulkMetadata;
    }
}
