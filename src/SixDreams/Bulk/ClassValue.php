<?php
declare(strict_types=1);

namespace SixDreams\Bulk;

/**
 * class ClassValue
 */
class ClassValue
{
    /** @var bool */
    private bool $initialised;

    /** @var mixed */
    private mixed $value;

    /**
     * ClassValue constructor.
     *
     * @param bool $initialised
     * @param mixed $value
     */
    private function __construct(bool $initialised, mixed $value)
    {
        $this->initialised = $initialised;
        $this->value = $value;
    }

    /**
     * Helper for building ClassValue from a class variable that is set to a value
     *
     * @param mixed $value
     * @return ClassValue
     */
    public static function initialised(mixed $value): ClassValue
    {
        return new ClassValue(true, $value);
    }

    /**
     * Helper for building ClassValue from a class variable the is not set
     *
     * @return ClassValue
     */
    public static function notInitialised(): ClassValue
    {
        return new ClassValue(false, null);
    }

    /**
     * Getter for Value
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Getter for Initialised
     *
     * @return bool
     */
    public function isInitialised(): bool
    {
        return $this->initialised;
    }
}
