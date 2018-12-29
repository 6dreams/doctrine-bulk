<?php
declare(strict_types = 1);

namespace SixDreams\Exceptions;

/**
 * Class CannotChangeWhereException
 */
final class CannotChangeWhereException extends \Exception
{
    /**
     * CannotChangeWhereException constructor.
     *
     * @param string $class
     * @param string $current
     * @param string $trying
     */
    public function __construct(string $class, string $current, string $trying)
    {
        parent::__construct(\sprintf(
            'Cannot change where criteria in "%s" from "%s" to "%s", because data is all ready set.',
            $class,
            $current,
            $trying
        ));
    }
}
