<?php

namespace PATA;

/**
 * Php Api Token Authentication Class
 */
class PATA {
    static $usersTableName;
    static $userTokensTableName;
    static $accessTokenName;
    static $refreshTokenName;
    static $activateTokenName;
    static $cookieRefreshTokenName;
    static $endpointRefreshToken;
    static $domainRefreshToken;

  /**
   * init()
   * Initialize the library passing dome configuration information.
   */
  public static function init($options = []){
    self::$usersTableName = $options["usersTableName"] ?? '';
    self::$userTokensTableName = $options["userTokensTableName"] ?? '';
    self::$accessTokenName = $options["accessTokenName"] ?? 'at';
    self::$refreshTokenName = $options["refreshTokenName"] ?? 'rt';
    self::$activateTokenName = $options["activateTokenName"] ?? 'act';
    self::$cookieRefreshTokenName = $options["cookieRefreshTokenName"] ?? 'rn_rt';
    self::$endpointRefreshToken = $options["endpointRefreshToken"] ?? '/auth/refresh-token';
    self::$domainRefreshToken = $options["domainRefreshToken"] ?? 'api-develop.ronchesisrl.it';

    if(!isset($options["dbHandler"])){
      require_once PATA_DB_PATH.'/LumenDB.php';
      $dbHandler = new LumenDB();
    }
    else{
      $dbHandler = $options["dbHandler"];
    }
	  DbHelper::init(["handler" => $dbHandler]);

    if(!isset($options["hashHandler"])){
      require_once PATA_SECURITY_PATH.'/LumenHash.php';
      $hashHandler = new LumenHash();
    }
    else{
      $hashHandler = $options["hashHandler"];
    }
	  HashHelper::init(["handler" => $hashHandler]);
  }

  /**
   * authenticate()
   * Take an access token and check if is valid/not expired
   */
  public static function authenticate($options = []){
    return AuthHelper::authenticate($options);
  }

  /**
   * refreshToken()
   * Takes an access token and refresh token and try to refresh a new access token
   * If refreshToken not passed try to get from cookies
   */
  static function refreshToken($options=[]){
    $refreshToken = $options["refreshToken"] ?? $_COOKIE[PATA::$cookieRefreshTokenName] ?? "";
    $options["refreshToken"] = $refreshToken;
    return AuthHelper::refreshToken($options);
  }

  /**
   * activate()
   * Searches provided activation token and check validity then set user activated and set activation token expired
   */
  static function activate($options=[]){
    return AuthHelper::activate($options);
  }

  /**
   * registerUser()
   * Creates a user with given email and password then send activation email. If user already exists.
   */
  static function registerUser($options=[]){
    return AuthHelper::registerUser($options);
  }

  /**
   * loginUser()
   * Check provided credentials then create a user session with refresh token, access token and session id. If provided credentials are wrong or usr isn't activated return an error
   */
  static function loginUser($options=[]){
    return AuthHelper::loginUser($options);
  }

  /**
   * logoutUser()
   * First executes authenticate() to check accessToken then delete user tokens associated to a specific sid
   */
  static function logoutUser($options=[]){
    return AuthHelper::logoutUser($options);
  }
}