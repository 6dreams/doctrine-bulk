<?php
declare(strict_types = 1);

namespace SixDreams\Bulk;

class ClassValue
{
    private bool $isInitialised;
    private $value;

    private function __construct(bool $isInitialised, mixed $value)
    {
        $this->isInitialised = $isInitialised;
        $this->value = $value;
    }

    public static function initialised(mixed $value): ClassValue
    {
        return new ClassValue(true, $value);
    }

    public static function notInitialised(): ClassValue
    {
        return new ClassValue(false, null);
    }

    public function value(): mixed
    {
        return $this->value;
    }

    public function isInitialised(): bool
    {
        return $this->isInitialised;
    }

}
