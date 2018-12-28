<?php
declare(strict_types = 1);

namespace SixDreams\Generator;

/**
 * Class SimpleHash
 */
final class SimpleHash
{
    /**
     * SimpleHash constructor.
     */
    private function __construct()
    {
    }

    /**
     * Generates simple hash for PRIMARY key, char(25).
     *
     * @param string[]|int[] $data
     *
     * @return string
     */
    public static function create(array $data)
    {
        return \base_convert(\md5(\implode('_', $data)), 16, 36);
    }
}
