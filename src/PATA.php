<?php
namespace PATA;

use PATA\Helpers\DbHelper;
use PATA\Helpers\HashHelper;
use PATA\Helpers\AuthHelper;
use PATA\Security\LumenHash;
use PATA\Db\LumenDB;

require_once 'constants.php';

/**
 * Php Api Token Authentication Class
 */
class PATA {
    public static $accessTokenName;
    public static $refreshTokenName;
    public static $activateTokenName;
    public static $changePasswordTokenName;
    public static $cookieRefreshTokenName;
    public static $endpointRefreshToken;
    public static $domainRefreshToken;

    /**
     * init()
     * Initialize the library passing dome configuration information.
     */
    public static function init($options = []) {
        self::$accessTokenName = $options['accessTokenName'] ?? PATA_DEFAULT_ACCESS_TOKEN;
        self::$refreshTokenName = $options['refreshTokenName'] ?? PATA_DEFAULT_REFRESH_TOKEN;
        self::$activateTokenName = $options['activateTokenName'] ?? PATA_DEFAULT_ACTIVATE_TOKEN;
        self::$changePasswordTokenName = $options['changePasswordTokenName'] ?? PATA_DEFAULT_CHANGE_PASSWORD_TOKEN;
        self::$cookieRefreshTokenName = $options['cookieRefreshTokenName'] ?? PATA_DEFAULT_COOKIE_REFRESH_TOKEN_NAME;
        self::$endpointRefreshToken = $options['endpointRefreshToken'] ?? PATA_DEFAULT_ENDPOINT_REFRESH_TOKEN;
        self::$domainRefreshToken = $options['domainRefreshToken'] ?? PATA_DEFAULT_DOMAIN_REFRESH_TOKEN;

        if (!isset($options['dbHandler'])) {
            // require_once PATA_DB_PATH.'/LumenDB.php';
            $dbHandler = new LumenDB();
        } else {
            $dbHandler = $options['dbHandler'];
        }
        DbHelper::init(['handler' => $dbHandler]);

        if (!isset($options['hashHandler'])) {
            // require_once PATA_SECURITY_PATH.'/LumenHash.php';
            $hashHandler = new LumenHash();
        } else {
            $hashHandler = $options['hashHandler'];
        }
        HashHelper::init(['handler' => $hashHandler]);
    }

    /**
     * authenticate()
     * Take an access token and check if is valid/not expired
     */
    public static function authenticate($options = []) {
        return AuthHelper::authenticate($options);
    }

    /**
     * refreshToken()
     * Takes an access token and refresh token and try to refresh a new access token
     * If refreshToken not passed try to get from cookies
     */
    public static function refreshToken($options = []) {
        $refreshToken = $options['refreshToken'] ?? $_COOKIE[PATA::$cookieRefreshTokenName] ?? '';
        $options['refreshToken'] = $refreshToken;
        return AuthHelper::refreshToken($options);
    }

    /**
     * activate()
     * Searches provided activation token and check validity then set user activated and set activation token expired
     */
    public static function activate($options = []) {
        return AuthHelper::activate($options);
    }

    /**
     * registerUser()
     * Creates a user with given email and password then send activation email. If user already exists.
     */
    public static function registerUser($options = []) {
        return AuthHelper::registerUser($options);
    }

    /**
     * loginUser()
     * Check provided credentials then create a user session with refresh token, access token and session id. If provided credentials are wrong or usr isn't activated return an error
     */
    public static function loginUser($options = []) {
        return AuthHelper::loginUser($options);
    }

    /**
     * logoutUser()
     * First executes authenticate() to check accessToken then delete user tokens associated to a specific sid
     */
    public static function logoutUser($options = []) {
        return AuthHelper::logoutUser($options);
    }

    /**
     * forgotPassword()
     * Check if email exists then send email with change password link
     */
    public static function forgotPassword($options = []) {
        return AuthHelper::forgotPassword($options);
    }

    /**
     * changePassword()
     * Check if password and token are valid then change password of the associated user
     */
    public static function changePassword($options = []) {
        return AuthHelper::changePassword($options);
    }
}
