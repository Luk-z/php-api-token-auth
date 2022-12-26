<?php
namespace PATA\Security;

class LumenHash implements Hash {
    public function hash(array $options = []): string {
        $password = $options['value'] ?? '';
        return app('hash')->make($password);
    }

    public function hashCheck(array $options = []): bool {
        $value = $options['value'] ?? '';
        $hashedValue = $options['hashedValue'] ?? '';
        return app('hash')->check($value, $hashedValue);
    }
}
