<?php
namespace PATA\Helpers;

class ValidateHelper {
    //@todo do some smarter cheks
    public static function refreshToken($options = []) {
        $value = $options['value'] ?? '';

        return !!$value;
    }

    //@todo do some smarter cheks
    public static function accessToken($options = []) {
        $value = $options['value'] ?? '';

        return !!$value;
    }

    //@todo do some smarter cheks
    public static function sidToken($options = []) {
        $value = $options['value'] ?? '';

        return !!$value;
    }

    public static function email($options = []) {
        $value = $options['value'] ?? '';

        return !!filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public static function password($options = []) {
        $value = $options['value'] ?? '';

        return !!preg_match(PATA_REGEX_PASSWORD, $value);
    }
}
