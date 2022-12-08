<?php

namespace PATA\Helpers;

class AuthHelper {
  /**
   * registerUser()
   * Creates a user with given email and password then send activation email. If user already exists.
   */
  static function registerUser($options=[]){
    $email = $options["email"] ?? "";
    $password = $options["password"] ?? "";

    ["data"=>["items" => $users]] = Db\DbHelper::selectUser(["email" => $email]);
    if(count($users)>0){
        return Helpers\AppHelper::returnError(["error"=>[
            "message" => "Email already exists",
            "code" => PATA_ERROR_REGISTRATION_EMAIL_EXITSTS,
        ]]);
    }

    $created = Helpers\DateTimeHelper::getMysqlUTC();

    ["result"=>$result,"error"=>["message"=>$message],"data"=>["id"=>$id]] = Db\DbHelper::createUser([
        "created" => $created,
        "password" => Security\HashHelper::hash(["value"=>$password]),
        "email" => $email,
        "active" => "0",
    ]) + ["data"=>["id"=>""], "error"=>["message"=>"Error Inserting User in DB"]];

    ["data"=>["queryResult" => $tokenInsertResult]] = Db\DbHelper::createToken(["data"=>[
        "created" => $created, 
        "modified" => $created, 
        "user_id" => $id, 
        "sid" => "",
        "token" => Helpers\AuthHelper::generateActivateAccountToken(),
        "token_type" => PATA::$activateTokenName,
        "expiration" => Helpers\DateTimeHelper::getAccessTokenExpiration(["date"=>$created]),
    ]]);

    //@todo send email

    if(!$result){
        return Helpers\AppHelper::returnError(["error"=>[
            "message" => $message,
            "code" => PATA_ERROR_REGISTRATION_CREATE,
            "fields" => ["id" => $id]
        ]]);
    }

    return Helpers\AppHelper::returnSuccess(["data"=>["id"=>$id, "emailSent" => true]]);
  }

  /**
   * loginUser()
   * Check provided credentials then create a user session with refresh token, access token and session id. If provided credentials are wrong or usr isn't activated return an error
   */
  static function loginUser($options=[]){
    $email = $options["email"] ?? "";
    $password = $options["password"] ?? "";

    $encryptedPassword = Security\HashHelper::hash(["value"=>$password]);
    ["data"=>["items" => $users]] = Db\DbHelper::selectUser([
        "email" => $email
    ]);

    if(count($users)<=0){
        return Helpers\AppHelper::returnError(["error"=>[
            "message" => "Wrong email",
            "code" => PATA_ERROR_WRONG_EMAIL,
        ]]);
    }

    if(!Security\HashHelper::hashCheck(["value"=>$password, "hashedValue"=>$users[0]->password])){
        return Helpers\AppHelper::returnError(["error"=>[
            "message" => "Wrong password",
            "code" => PATA_ERROR_WRONG_PASSWORD,
        ]]);
    }

    if(!$users[0]->active){
        return Helpers\AppHelper::returnError(["error"=>[
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

    return Helpers\AppHelper::returnSuccess([
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
    $sid = $options["sid"] ?? null;
    $accessToken = $options["accessToken"] ?? null;

    $authResult = self::authenticate(["accessToken"=>$accessToken]);

    if(!$authResult["result"]){
        return Helpers\AppHelper::returnError([ 
            "error" => $authResult["error"] ?? []
        ]);
    }

    ["data" => ["sid" => $sid]] = $authResult;

    //deleteToken uses Helpers\AppHelper::returnSuccess
    return Db\DbHelper::deleteToken(["sid" => $sid]);
  }

  /**
   * activate()
   * Searches provided activation token and check validity then set user activated and set activation token expired
   */
  static function activate($options=[]){
    $token = $options["token"] ?? null;

    //select token
    ["data"=>["items" => $items]] = Db\DbHelper::selectActivateUserToken([
        "token" => $token
    ]);

    if(count($items)<1){
        return Helpers\AppHelper::returnError(["error"=>[
            "message" => "Token not found",
            "code" => PATA_ERROR_ACTIVATE_TOKEN_NOTFOUND
        ]]);
    }

    if(count($items)>1){
        return Helpers\AppHelper::returnError(["error"=>[
            "message" => "Duplicated Token",
            "code" => PATA_ERROR_ACTIVATE_DUPLICATED_TOKEN
        ]]);
    }
    
    ["result" => $hasExpired] = Helpers\DateTimeHelper::hasExpired([
        "date"=> intval($items[0]->expiration)
    ]);
    if ($hasExpired) {
        if(intval($items[0]->expiration) === intval(PATA_TOKEN_EXPIRATION_VALUE)){
            return Helpers\AppHelper::returnError(["error"=>[
                "message" => "Token already used. Try to login.",
                "code" => PATA_ERROR_ACTIVATE_TOKEN_USED
            ]]);
        }

        //error token expired, suggest to try login and press resend activation email
        return Helpers\AppHelper::returnError(["error"=>[
            "message" => "Token Expired. Try to login and press the send activation email button",
            "code" => PATA_ERROR_ACTIVATE_TOKEN_EXPIRED
        ]]);
    }

    //ok activate user and return true
    ["data"=>["queryResult" => $queryResult]] = Db\DbHelper::updateUser([
        "data"=>["active"=>true], 
        "id"=>$items[0]->user_id
    ]);

    ["data"=>["queryResult" => $queryResult]] = Db\DbHelper::updateToken([
        "data"=>["expiration"=>PATA_TOKEN_EXPIRATION_VALUE], 
        "id"=>$items[0]->id
    ]);
    
    if($queryResult===1){
        return Helpers\AppHelper::returnSuccess(["data"=>["queryResult"=>$queryResult]]);
    }

    return Helpers\AppHelper::returnError(["error"=>[
        "message" => "Error updating token",
        "code" => PATA_ERROR_ACTIVATE_TOKEN_DB_ERROR
    ]]);
  }

  /**
   * authenticate()
   * Take an access token and check if is valid/not expired
   */
  public static function authenticate($options = []){
    $accessToken = $options["accessToken"] ?? "";
    $checkExpired = $options["checkExpired"] ?? true;

    // 1. check if token is not empty
    if(!$accessToken){
        return Helpers\AppHelper::returnError([ "error" =>[
            "message" => "Access Token not valid",
            "code" => PATA_ERROR_AUTH_INVALID_TOKEN
        ]]);
    }

    // 2. check if token is found in db
    ["data" => ["items" => $items]] = Db\DbHelper::selectAccessToken(["token" => $accessToken]);
    if(count($items)<1){
        return Helpers\AppHelper::returnError([ "error" =>[
            "message" => "Access Token not found",
            "code" => PATA_ERROR_AUTH_TOKEN_NOT_FOUND
        ]]);
    }
    if(count($items)>1){
        //@todo send telegram
        return Helpers\AppHelper::returnError([ "error" =>[
            "message" => "Access Token duplicated",
            "code" => PATA_ERROR_AUTH_TOKEN_DUPLICATED
        ]]);
    }

    // 3. check if token is not expired
    if ($checkExpired) {
        ["result" => $hasExpired] = Helpers\DateTimeHelper::hasExpired([
            "date" => intval($items[0]->expiration)
        ]);
        if ($hasExpired) {
            ["data" => ["queryResult" => $queryResult]] = Db\DbHelper::deleteToken(["sid" => $items[0]->sid]);

            return Helpers\AppHelper::returnError(["error" => [
                "message" => "Access Token expired",
                "code" => PATA_ERROR_AUTH_TOKEN_EXPIRED
            ]]);
        }
    }

    return Helpers\AppHelper::returnSuccess(["data"=>["sid"=>$items[0]->sid]]);
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
    if(!Helpers\ValidateHelper::refreshToken(["value"=>$refreshToken])){
        return Helpers\AppHelper::returnError([ 
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
        return Helpers\AppHelper::returnError([ 
            "error" =>$authResult["error"] ?? [],
            "customData" => ["responseCode" => 401]
        ]);
    }

    ["data" => ["sid" => $sid]] = $authResult ?? ["data"=>["sid"=>""]];

    // 4. if refresh token is found
    ["data" => ["items" => $items]] = Db\DbHelper::selectRefreshToken(["token" => $refreshToken]);
    if(count($items)<1){
        return Helpers\AppHelper::returnError([ 
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
    ["result" => $hasExpired] = Helpers\DateTimeHelper::hasExpired([
        "date" => intval($items[0]->expiration)
    ]);
    if ($hasExpired) {
        ["data" => ["queryResult" => $queryResult]] = Db\DbHelper::deleteToken(["sid" => $items[0]->sid]);

        return Helpers\AppHelper::returnError([
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
        return Helpers\AppHelper::returnError([ 
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
        // Helpers\AppHelper::sendTelegram();
        return Helpers\AppHelper::returnError([ 
            "error" =>[
                "message" => "Access Token duplicated",
                "code" => PATA_ERROR_REFRESH_TOKEN_DUPLICATED
            ],
            "customData" => ["responseCode" => 401]
        ]);
    }

    // 7. burn token and refresh token with same sid
    ["data" => ["queryResult" => $deleteTokensResult]] = Db\DbHelper::deleteToken(["sid" => $items[0]->sid]);
    
    // 8. generate new sdi, at, rt
    ["data"=>[
        "sid"=>$sid, 
        "refreshToken"=>$refreshToken, 
        "accessToken"=>$accessToken,
        "setCookieResult"=>$setCookieResult,
        "tokenInsertResult"=>$tokenInsertResult,
    ]] = self::generateAndSaveUserTokens(["userId"=>$items[0]->user_id]);

    return Helpers\AppHelper::returnSuccess(["data"=>[
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

  public static function generateAndSaveUserTokens($options = []) {
    $userId = $options["userId"] ?? "";

    //generate access token
    $accessToken = self::generateAccessToken();

    //generate refresh token
    $refreshToken = self::generateRefreshToken();

    //generate secure identifier
    $sid = self::generateSid();

    //save on db
    $created = Helpers\DateTimeHelper::getMysqlUTC();
    $baseInsert = ["created" => $created, "modified" => $created, "user_id" => $userId, "sid" => $sid];
    ["data"=>["queryResult" => $tokenInsertResult]] = Db\DbHelper::createToken(["data"=>[
        $baseInsert + [
            "token" => $accessToken,
            "token_type" => PATA::$accessTokenName,
            "expiration" => Helpers\DateTimeHelper::getAccessTokenExpiration(["date"=>$created]),
        ],
        $baseInsert + [
            "token" => $refreshToken,
            "token_type" => PATA::$refreshTokenName,
            "expiration" => Helpers\DateTimeHelper::getRefreshTokenExpiration(),
        ],
    ]]);

    // @todo DELETE FROM `users_app_tokens` ORDER BY `created_at` DESC limit ($numAccessToken-10)
    // ["items" => $numAccessToken] = Db\DbHelper::selectAccessToken(["count" => true, "userId" => $users[0]->id]);
    // if($numAccessToken>10){}

    //send user refresh token in httpOnly,secure and path="/auth/refresh-token"
    ["result" => $setCookieRes] = self::setRefreshTokenCookie(["rt" => $refreshToken]);

    return Helpers\AppHelper::returnSuccess(["data"=>[
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
          return Helpers\AppHelper::returnError();
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
        Helpers\AppHelper::returnSuccess();
      }

      Helpers\AppHelper::returnError();
  }
}