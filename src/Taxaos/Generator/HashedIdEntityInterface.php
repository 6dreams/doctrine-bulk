<?php
declare(strict_types = 1);

namespace Taxaos\Generator;

/**
 * Class HashesIdEntityInterface
 */
interface HashedIdEntityInterface
{
    /**
     * This function must return values for ID generation.
     *
     * @return string[]|int[]|float[]
     */
    public function getHashGeneratorValues(): array;

    /**
     * This function must return field names for ID generation.
     *
     * @return string[]
     */
    public function getHashGeneratorFields(): array;

    /**
     * Transform field value to hash-id representation.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return mixed
     */
    public function getHashGeneratorFieldValue(string $name, $value): mixed;
}
