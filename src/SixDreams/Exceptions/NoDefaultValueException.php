<?php
declare(strict_types = 1);

namespace SixDreams\Exceptions;

final class NoDefaultValueException extends SixDreamsException
{
    public function __construct(string $name, string $class)
    {
        parent::__construct(\sprintf('No default value for field "%s" of "%s"', $name, $class));
    }
}
