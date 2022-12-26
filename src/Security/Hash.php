<?php
namespace PATA\Security;

/**
 * Hash Interface
 */
interface Hash {
    /**
     * hash() see https://laravel.com/docs/9.x/hashing#hashing-passwords
     *
     * @param array options['value'] - the value to hash
     *
     * @return string - hashed value
     */
    public function hash(array $options): string;

    /**
     * hashCheck() see https://laravel.com/docs/9.x/hashing#verifying-that-a-password-matches-a-hash
     * hash value and check it is equal to hashedValue
     *
     * @param array options['value'] - the value not hashed to check with hashedValue
     * @param array options['hashedValue'] - the hashed value to compare
     *
     * @return bool - whether value hashed = hashedValue
     */
    public function hashCheck(array $options): bool;
}
