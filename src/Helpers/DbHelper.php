<?php
namespace PATA\Helpers;

use PATA\PATA;

class DbHelper {
    private static $db = null;

    public static function init($options = []) {
        $handler = $options['handler'] ?? false;

        if ($handler) {
            self::$db = $handler;
        }
    }

    // to use outside this class
    public static function getInstance() {
        if (!self::$db) {
            //die bruttaly
            die("PATA/DbHelper::getInstance db not setted. DbHelper::init(['handler'=>your_db_handle]) must be called.");
        }
        return self::$db;
    }

    /**
     * TOKEN
     */
    public static function selectActivateUserToken($options = []) {
        //@todo maybe cache value
        return self::selectToken($options + ['type' => PATA::$activateTokenName]);
    }

    public static function selectAccessToken($options = []) {
        //@todo maybe cache value
        return self::selectToken($options + ['type' => PATA::$accessTokenName]);
    }

    public static function selectRefreshToken($options = []) {
        //@todo maybe cache value
        return self::selectToken($options + ['type' => PATA::$refreshTokenName]);
    }

    public static function selectToken($options = []) {
        return self::$db->selectToken($options);
    }

    public static function createToken($options = []) {
        return self::$db->createToken($options);
    }

    public static function updateToken($options = []) {
        return self::$db->updateToken($options);
    }

    public static function deleteToken($options = []) {
        return self::$db->deleteToken($options);
    }

    /**
     * USER
     */

    //create user
    public static function createUser($options = []) {
        return self::$db->createUser($options);
    }

    public static function updateUser($options = []) {
        return self::$db->updateUser($options);
    }

    public static function selectUser($options = []) {
        return self::$db->selectUser($options);
    }
}
