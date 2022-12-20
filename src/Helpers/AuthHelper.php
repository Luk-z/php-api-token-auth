<?php

namespace PATA\Helpers;

use PATA\PATA;
use PATA\Helpers\HashHelper;

class AuthHelper {
    /**
   * authenticate()
   * Take an access token and check if is valid/not expired
   */
  public static function authenticate($options = []){
    $accessToken = $options["accessToken"] ?? "";
    $checkExpired = $options["checkExpired"] ?? true;

    // 1. check if token is not empty
    if(!$accessToken){
        return AppHelper::returnError([ "error" =>[
            "message" => "Access Token not valid",
            "code" => PATA_ERROR_AUTH_INVALID_TOKEN
        ]]);
    }

    // 2. check if token is found in db
    ["data" => ["items" => $items]] = DbHelper::selectAccessToken(["token" => $accessToken]);
    if(count($items)<1){
        return AppHelper::returnError([ "error" =>[
            "message" => "Access Token not found",
            "code" => PATA_ERROR_AUTH_TOKEN_NOT_FOUND
        ]]);
    }
    if(count($items)>1){
        //@todo send telegram
        return AppHelper::returnError([ "error" =>[
            "message" => "Access Token duplicated",
            "code" => PATA_ERROR_AUTH_TOKEN_DUPLICATED
        ]]);
    }

    // 3. check if token is not expired
    if ($checkExpired) {
        ["result" => $hasExpired] = DateTimeHelper::hasExpired([
            "date" => intval($items[0]->expiration)
        ]);
        if ($hasExpired) {
            ["data" => ["queryResult" => $queryResult]] = DbHelper::deleteToken(["sid" => $items[0]->sid]);

            return AppHelper::returnError(["error" => [
                "message" => "Access Token expired",
                "code" => PATA_ERROR_AUTH_TOKEN_EXPIRED
            ]]);
        }
    }

    return AppHelper::returnSuccess(["data"=>[
        "sid" => $items[0]->sid,
        "userId" => $items[0]->user_id,
    ]]);
  }

  /**
   * refreshToken()
   * Takes an access token and refresh token and try to refresh a new access token
   * If refreshToken not passed try to get from cookies
   */
  static function refreshToken($options=[]){
    $accessToken = $options["accessToken"] ?? "";
    $refreshToken = $options["refreshToken"] ?? "";

    // 1. if refresh token exists
    // 2. if refresh token is valid
    if(!ValidateHelper::refreshToken(["value"=>$refreshToken])){
        return AppHelper::returnError([ 
            "error" =>[
                "message" => "Refresh Token not valid",
                "code" => PATA_ERROR_REFRESH_TOKEN_INVALID
            ],
            "customData" => ["responseCode" => 422]
        ]);
    }

    // 3. if access token is valid or expired
    $authResult = self::authenticate(["accessToken"=>$accessToken, "checkExpired"=>false]);
    if(!$authResult["result"]){
        return AppHelper::returnError([ 
            "error" =>$authResult["error"] ?? [],
            "customData" => ["responseCode" => 401]
        ]);
    }

    ["data" => ["sid" => $sid]] = $authResult ?? ["data"=>["sid"=>""]];

    // 4. if refresh token is found
    ["data" => ["items" => $items]] = DbHelper::selectRefreshToken(["token" => $refreshToken]);
    if(count($items)<1){
        return AppHelper::returnError([ 
            "error" =>[
                "message" => "Refresh Token not found",
                "code" => PATA_ERROR_REFRESH_TOKEN_NOT_FOUND,
                "rt" => $refreshToken,
                "sid" => $sid,
            ],
            "customData" => ["responseCode" => 401]
        ]);
    }

    // 5. if refresh token is not expired
    ["result" => $hasExpired] = DateTimeHelper::hasExpired([
        "date" => intval($items[0]->expiration)
    ]);
    if ($hasExpired) {
        ["data" => ["queryResult" => $queryResult]] = DbHelper::deleteToken(["sid" => $items[0]->sid]);

        return AppHelper::returnError([
            "error" => [
                "message" => "Refresh Token expired",
                "code" => PATA_ERROR_REFRESH_TOKEN_EXPIRED,
                "rt" => $refreshToken,
            ],
            "customData" => ["responseCode" => 401]
        ]);
    }

    // 6. if refresh token and access tokena have same sid
    if($items[0]->sid !== $sid){
        return AppHelper::returnError([ 
            "error" =>[
                "message" => "Refresh Token and Access Token have different sid",
                "code" => PATA_ERROR_REFRESH_TOKEN_DIFFERENT_SID,
                "at_sid" => $sid,
                "rt_sid" => $items[0]->sid,
            ],
            "customData" => ["responseCode" => 401]
        ]);
    }

    // also check for duplicate, should never happens
    if(count($items)>1){
        // AppHelper::sendTelegram();
        return AppHelper::returnError([ 
            "error" =>[
                "message" => "Access Token duplicated",
                "code" => PATA_ERROR_REFRESH_TOKEN_DUPLICATED
            ],
            "customData" => ["responseCode" => 401]
        ]);
    }

    // 7. burn token and refresh token with same sid
    ["data" => ["queryResult" => $deleteTokensResult]] = DbHelper::deleteToken(["sid" => $items[0]->sid]);
    
    // 8. generate new sdi, at, rt
    ["data"=>[
        "sid"=>$sid, 
        "refreshToken"=>$refreshToken, 
        "accessToken"=>$accessToken,
        "setCookieResult"=>$setCookieResult,
        "tokenInsertResult"=>$tokenInsertResult,
    ]] = self::generateAndSaveUserTokens(["userId"=>$items[0]->user_id]);

    return AppHelper::returnSuccess(["data"=>[
        "sid"=>$sid,
        "refreshToken"=>$refreshToken,
        "accessToken"=>$accessToken,
        "debug"=>[
            "setCookieResult"=>$setCookieResult,
            "tokenInsertResult"=>$tokenInsertResult,
            "deleteTokensResult"=>$deleteTokensResult,
        ]
    ]]);
  }

  /**
   * activate()
   * Searches provided activation token and check validity then set user activated and set activation token expired
   */
  static function activate($options=[]){
    $token = $options["token"] ?? null;

    if($token === null){
        return AppHelper::returnError(["error"=>[
            "message" => "Token not found",
            "code" => PATA_ERROR_ACTIVATE_TOKEN_NOTFOUND
        ]]);
    }

    //select token
    ["data"=>["items" => $items]] = DbHelper::selectActivateUserToken([
        "token" => $token
    ]);

    if(count($items)<1){
        return AppHelper::returnError(["error"=>[
            "message" => "Token not found",
            "code" => PATA_ERROR_ACTIVATE_TOKEN_NOTFOUND
        ]]);
    }

    if(count($items)>1){
        return AppHelper::returnError(["error"=>[
            "message" => "Duplicated Token",
            "code" => PATA_ERROR_ACTIVATE_DUPLICATED_TOKEN
        ]]);
    }
    
    ["result" => $hasExpired] = DateTimeHelper::hasExpired([
        "date"=> intval($items[0]->expiration)
    ]);
    if ($hasExpired) {
        if(intval($items[0]->expiration) === intval(PATA_TOKEN_EXPIRATION_VALUE)){
            return AppHelper::returnError(["error"=>[
                "message" => "Token already used. Try to login.",
                "code" => PATA_ERROR_ACTIVATE_TOKEN_USED
            ]]);
        }

        //error token expired, suggest to try login and press resend activation email
        return AppHelper::returnError(["error"=>[
            "message" => "Token Expired. Try to login and press the send activation email button",
            "code" => PATA_ERROR_ACTIVATE_TOKEN_EXPIRED
        ]]);
    }

    //ok activate user and return true
    ["data"=>["queryResult" => $queryResult]] = DbHelper::updateUser([
        "data"=>["active"=>true], 
        "id"=>$items[0]->user_id
    ]);

    ["data"=>["queryResult" => $queryResult]] = DbHelper::updateToken([
        "data"=>["expiration"=>PATA_TOKEN_EXPIRATION_VALUE], 
        "id"=>$items[0]->id
    ]);
    
    if($queryResult===1){
        return AppHelper::returnSuccess(["data"=>["queryResult"=>$queryResult]]);
    }

    return AppHelper::returnError(["error"=>[
        "message" => "Error updating token",
        "code" => PATA_ERROR_ACTIVATE_TOKEN_DB_ERROR
    ]]);
  }

  /**
   * registerUser()
   * Creates a user with given email and password then send activation email. If user already exists.
   */
  static function registerUser($options=[]){
    $email = $options["email"] ?? "";
    $password = $options["password"] ?? "";

    if (!ValidateHelper::email(["value" => $email])){
        return AppHelper::returnError(["error"=>[
            "message" => "Wrong email",
            "code" => PATA_ERROR_REGISTRATION_INVALID_EMAIL,
            "fields" => [["name"=>"email"]]
        ]]);
    }

    if (!ValidateHelper::password(["value" => $password])){
        return AppHelper::returnError(["error"=>[
            "message" => "Wrong password",
            "code" => PATA_ERROR_REGISTRATION_INVALID_PASSWORD,
            "fields" => [["name"=>"password"]]
        ]]);
    }

    ["data"=>["items" => $users]] = DbHelper::selectUser(["email" => $email]);
    if(count($users)>0){
        return AppHelper::returnError(["error"=>[
            "message" => "Email already exists",
            "code" => PATA_ERROR_REGISTRATION_EMAIL_EXITSTS,
        ]]);
    }

    $created = DateTimeHelper::getMysqlUTC();

    ["result"=>$result,"error"=>["message"=>$message],"data"=>["id"=>$id]] = DbHelper::createUser(["data"=>[
        "created" => $created,
        "password" => HashHelper::hash(["value"=>$password]),
        "email" => $email,
        "active" => "0",
    ]]) + ["data"=>["id"=>""], "error"=>["message"=>"Error Inserting User in DB"]];

    ["data"=>["queryResult" => $tokenInsertResult]] = DbHelper::createToken(["data"=>[
        "created" => $created, 
        "modified" => $created, 
        "user_id" => $id, 
        "sid" => "",
        "token" => AuthHelper::generateActivateAccountToken(),
        "token_type" => PATA::$activateTokenName,
        "expiration" => DateTimeHelper::getAccessTokenExpiration(["date"=>$created]),
    ]]);

    //@todo send email

    if(!$result){
        return AppHelper::returnError(["error"=>[
            "message" => $message,
            "code" => PATA_ERROR_REGISTRATION_CREATE,
            "fields" => ["id" => $id]
        ]]);
    }

    return AppHelper::returnSuccess(["data"=>["id"=>$id, "emailSent" => true]]);
  }

  /**
   * loginUser()
   * Check provided credentials then create a user session with refresh token, access token and session id. If provided credentials are wrong or usr isn't activated return an error
   */
  static function loginUser($options=[]){
    $email = $options["email"] ?? "";
    $password = $options["password"] ?? "";

    if (!ValidateHelper::email(["value" => $email])){
        return AppHelper::returnError(["error"=>[
            "message" => "Wrong email",
            "code" => PATA_ERROR_LOGIN_INVALID_EMAIL,
            "fields" => [["name"=>"email"]]
        ]]);
    }

    if (!ValidateHelper::password(["value" => $password])){
        return AppHelper::returnError(["error"=>[
            "message" => "Wrong password",
            "code" => PATA_ERROR_LOGIN_INVALID_PASSWORD,
            "fields" => [["name"=>"password"]]
        ]]);
    }

    $encryptedPassword = HashHelper::hash(["value"=>$password]);
    ["data"=>["items" => $users]] = DbHelper::selectUser([
        "email" => $email
    ]);

    if(count($users)<=0){
        return AppHelper::returnError(["error"=>[
            "message" => "Wrong email",
            "code" => PATA_ERROR_WRONG_EMAIL,
        ]]);
    }

    if(!HashHelper::hashCheck(["value"=>$password, "hashedValue"=>$users[0]->password])){
        return AppHelper::returnError(["error"=>[
            "message" => "Wrong password",
            "code" => PATA_ERROR_WRONG_PASSWORD,
        ]]);
    }

    if(!$users[0]->active){
        return AppHelper::returnError(["error"=>[
            "message" => "User not active",
            "code" => PATA_ERROR_USER_NOT_ACTIVE,
        ]]);
    }

    ["data"=>[
        "sid"=>$sid, 
        "refreshToken"=>$refreshToken, 
        "accessToken"=>$accessToken,
        "setCookieResult"=>$setCookieResult,
        "tokenInsertResult"=>$tokenInsertResult,
    ]] = self::generateAndSaveUserTokens(["userId"=>$users[0]->id]);

    return AppHelper::returnSuccess([
        "data"=>[
            "user" => $users[0],
            "accessToken" => $accessToken,
            "sid" => $sid,
            "debug" => [
                "rtResult" => $setCookieResult,
                "tokenInsertResult" => $tokenInsertResult
            ],
        ]
    ]);
  }

  /**
   * logoutUser()
   * First executes authenticate() to check accessToken then delete user tokens associated to a specific sid
   */
  static function logoutUser($options=[]){
    $accessToken = $options["accessToken"] ?? null;

    $authResult = self::authenticate(["accessToken"=>$accessToken]);

    if(!$authResult["result"]){
        return AppHelper::returnError([ 
            "error" => $authResult["error"] ?? []
        ]);
    }

    ["data" => ["sid" => $sid]] = $authResult;

    //deleteToken uses AppHelper::returnSuccess
    return DbHelper::deleteToken(["sid" => $sid]);
  }

  public static function generateAndSaveUserTokens($options = []) {
    $userId = $options["userId"] ?? "";

    //generate access token
    $accessToken = self::generateAccessToken();

    //generate refresh token
    $refreshToken = self::generateRefreshToken();

    //generate secure identifier
    $sid = self::generateSid();

    //save on db
    $created = DateTimeHelper::getMysqlUTC();
    $baseInsert = ["created" => $created, "modified" => $created, "user_id" => $userId, "sid" => $sid];
    ["data"=>["queryResult" => $tokenInsertResult]] = DbHelper::createToken(["data"=>[
        $baseInsert + [
            "token" => $accessToken,
            "token_type" => PATA::$accessTokenName,
            "expiration" => DateTimeHelper::getAccessTokenExpiration(["date"=>$created]),
        ],
        $baseInsert + [
            "token" => $refreshToken,
            "token_type" => PATA::$refreshTokenName,
            "expiration" => DateTimeHelper::getRefreshTokenExpiration(),
        ],
    ]]);

    // @todo DELETE FROM `users_app_tokens` ORDER BY `created_at` DESC limit ($numAccessToken-10)
    // ["items" => $numAccessToken] = DbHelper::selectAccessToken(["count" => true, "userId" => $users[0]->id]);
    // if($numAccessToken>10){}

    //send user refresh token in httpOnly,secure and path="/auth/refresh-token"
    ["result" => $setCookieRes] = self::setRefreshTokenCookie(["rt" => $refreshToken]);

    return AppHelper::returnSuccess(["data"=>[
        "sid"=>$sid, 
        "refreshToken"=>$refreshToken, 
        "accessToken"=>$accessToken,
        "setCookieResult"=>$setCookieRes,
        "tokenInsertResult"=>$tokenInsertResult,
    ]]);
  }

  static function generateAccessToken($options=[]){
    return self::generateToken();
  }

  static function generateRefreshToken($options=[]){
      return self::generateToken(["length"=> 32]);
  }

  static function generateSid($options=[]){
      return self::generateToken(["length"=> 16]);
  }

  static function generateActivateAccountToken($options=[]){
      return self::generateToken(["length"=> 32]);
  }

  public static function generateToken($options = []) {
      $length = isset($options['length']) ? $options['length'] : 64;

      //Generate a random string.
      $token = random_bytes($length);

      //Convert the binary data into hexadecimal representation.
      $token = bin2hex($token);

      //Print it out for example purposes.
      return $token;
  }  

  public static function setRefreshTokenCookie($options = []) {
      $rt = $options["rt"] ?? "";

      // this check is for phpunit
      if (headers_sent() !== false) {
          return AppHelper::returnError();
      }

      $res = setcookie(
          PATA::$cookieRefreshTokenName, //name
          $rt, //value
          NULL, //expires_or_options
          PATA::$endpointRefreshToken, //path
          PATA::$domainRefreshToken, //domain
          true, //secure
          true, //httponly
      );

      if($res){
        AppHelper::returnSuccess();
      }

      AppHelper::returnError();
  }
}