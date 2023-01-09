<?php
namespace PATA\Security;

class FakeHash implements Hash {
    public function hash(array $options = []): string {
        $password = $options['value'] ?? '';
        return $password;
    }

    public function hashCheck(array $options = []): bool {
        $value = $options['value'] ?? '';
        $hashedValue = $options['hashedValue'] ?? '';

        return $value === $hashedValue;
    }
}
