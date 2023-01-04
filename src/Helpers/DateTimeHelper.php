<?php
namespace PATA\Helpers;

class DateTimeHelper {
    public static function getMysqlUTC($options = []) {
        return (new \DateTime())->format('Y-m-d H:i:s');
    }

    public static function getUTCTimestamp($options = []) {
        $date = $options['date'] ?? '';
        $format = $options['format'] ?? PATA_DATE_FORMAT_MYSQL;

        if ($date) {
            $dateTime = \DateTime::createFromFormat($format, $date);
        } else {
            $dateTime = new \DateTime();
        }

        return $dateTime->getTimestamp();
    }

    public static function getAccessTokenExpiration($options = []) {
        return self::getUTCTimestamp($options) + PATA_ACCESS_TOKEN_DURATION;
    }

    public static function getActivateAccountTokenExpiration($options = []) {
        return self::getUTCTimestamp($options) + PATA_ACTIVATE_ACCOUNT_TOKEN_DURATION;
    }

    public static function getChangePasswordTokenExpiration($options = []) {
        return self::getUTCTimestamp($options) + PATA_CHANGE_PASSWORD_TOKEN_DURATION;
    }

    public static function getRefreshTokenExpiration($options = []) {
        return PATA_TOKEN_EXPIRATION_VALUE_INFINITE;
    }

    // public static function getActivateAccountTokenExpiration($options=[]){
    //   return self::getUTCTimestamp($options) + PATA_ACTIVATE_ACCOUNT_TOKEN_DURATION;
    // }

    public static function hasExpired($options = []) {
        $expirationDate = $options['date'] ?? 0;
        $expirationDateFormat = $options['format'] ?? null;

        if (is_string($expirationDate)) {
            $expirationDateTimestamp = self::getUTCTimestamp([
                'date' => $expirationDate,
                'format' => $expirationDateFormat,
            ]);
        } else {
            $expirationDateTimestamp = $expirationDate;
        }

        if ($expirationDateTimestamp === PATA_TOKEN_EXPIRATION_VALUE_INFINITE) {
            return ['result' => false, 'diff' => 0];
        }

        return [
            'result' => self::getUTCTimestamp() > $expirationDateTimestamp,
            'diff' => $expirationDateTimestamp - self::getUTCTimestamp()
        ];
    }
}
