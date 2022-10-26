<?php declare(strict_types=1);

use Ulid\Ulid;

if(!function_exists('is_ulid')){
    /**
     * Ulidの検証をする
     *
     * @param mixed $value
     * @return boolean
     */
    function is_ulid(mixed $value): bool
    {
        $value = (string)$value;
        $value = strtoupper($value);
        if (!preg_match(sprintf('!^[%s]{%d}$!', Ulid::ENCODING_CHARS, Ulid::TIME_LENGTH + Ulid::RANDOM_LENGTH), $value)) {
            return false;
        }

        return true;
    }
}
