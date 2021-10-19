<?php
declare(strict_types = 1);

namespace SixDreams\Exceptions;

final class ValueNotInitialisedException extends SixDreamsException
{
    public function __construct(string $name, string $class)
    {
        parent::__construct(\sprintf('Must initialise field "%s" of "%s"', $name, $class));
    }
}
