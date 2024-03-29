<?php
namespace PATA\Helpers;

use PATA\PATA;
use PATA\Helpers\HashHelper;

class AuthHelper {
    /**
   * authenticate()
   * Take an access token and check if is valid/not expired
   */
    public static function authenticate($options = []) {
        $accessToken = $options['accessToken'] ?? '';
        $checkExpired = $options['checkExpired'] ?? true;

        // 1. check if token is not empty
        if (!$accessToken) {
            return AppHelper::returnError(['error' => [
                'message' => 'Access Token not valid',
                'code' => PATA_ERROR_AUTH_INVALID_TOKEN
            ]]);
        }

        // 2. check if token is found in db
        ['data' => ['items' => $items]] = DbHelper::selectAccessToken(['token' => $accessToken]);
        if (count($items) < 1) {
            return AppHelper::returnError(['error' => [
                'message' => 'Access Token not found',
                'code' => PATA_ERROR_AUTH_TOKEN_NOT_FOUND
            ]]);
        }
        if (count($items) > 1) {
            //@todo send telegram
            return AppHelper::returnError(['error' => [
                'message' => 'Access Token duplicated',
                'code' => PATA_ERROR_AUTH_TOKEN_DUPLICATED
            ]]);
        }

        // 3. check if token is not expired
        if ($checkExpired) {
            ['result' => $hasExpired] = DateTimeHelper::hasExpired([
                'date' => intval($items[0]->expiration)
            ]);
            if ($hasExpired) {
                ['data' => ['queryResult' => $queryResult]] = DbHelper::deleteToken(['sid' => $items[0]->sid ?: '']);

                return AppHelper::returnError(['error' => [
                    'message' => 'Access Token expired',
                    'code' => PATA_ERROR_AUTH_TOKEN_EXPIRED
                ]]);
            }
        }

        return AppHelper::returnSuccess(['data' => [
            'sid' => $items[0]->sid,
            'userId' => $items[0]->user_id,
        ]]);
    }

    /**
     * refreshToken()
     * Takes an access token and refresh token and try to refresh a new access token
     * If refreshToken not passed try to get from cookies
     */
    public static function refreshToken($options = []) {
        $accessToken = $options['accessToken'] ?? '';
        $refreshToken = $options['refreshToken'] ?? '';

        // 1. if refresh token exists
        // 2. if refresh token is valid
        if (!ValidateHelper::refreshToken(['value' => $refreshToken])) {
            return AppHelper::returnError([
                'error' => [
                    'message' => 'Refresh Token not valid',
                    'code' => PATA_ERROR_REFRESH_TOKEN_INVALID
                ],
                'customData' => ['responseCode' => 422]
            ]);
        }

        // 3. if access token is valid or expired
        $authResult = self::authenticate(['accessToken' => $accessToken, 'checkExpired' => false]);
        if (!$authResult['result']) {
            return AppHelper::returnError([
                'error' => $authResult['error'] ?? [],
                'customData' => ['responseCode' => 401]
            ]);
        }

        ['data' => ['sid' => $sid]] = $authResult ?? ['data' => ['sid' => '']];

        // 4. if refresh token is found
        ['data' => ['items' => $items]] = DbHelper::selectRefreshToken(['token' => $refreshToken]);
        if (count($items) < 1) {
            return AppHelper::returnError([
                'error' => [
                    'message' => 'Refresh Token not found',
                    'code' => PATA_ERROR_REFRESH_TOKEN_NOT_FOUND,
                    'rt' => $refreshToken,
                    'sid' => $sid,
                ],
                'customData' => ['responseCode' => 401]
            ]);
        }

        // 5. if refresh token is not expired
        ['result' => $hasExpired] = DateTimeHelper::hasExpired([
            'date' => intval($items[0]->expiration)
        ]);
        if ($hasExpired) {
            ['data' => ['queryResult' => $queryResult]] = DbHelper::deleteToken(['sid' => $items[0]->sid ?: '']);

            return AppHelper::returnError([
                'error' => [
                    'message' => 'Refresh Token expired',
                    'code' => PATA_ERROR_REFRESH_TOKEN_EXPIRED,
                    'rt' => $refreshToken,
                ],
                'customData' => ['responseCode' => 401]
            ]);
        }

        // 6. if refresh token and access tokena have same sid
        if ($items[0]->sid !== $sid) {
            return AppHelper::returnError([
                'error' => [
                    'message' => 'Refresh Token and Access Token have different sid',
                    'code' => PATA_ERROR_REFRESH_TOKEN_DIFFERENT_SID,
                    'at_sid' => $sid,
                    'rt_sid' => $items[0]->sid,
                ],
                'customData' => ['responseCode' => 401]
            ]);
        }

        // also check for duplicate, should never happens
        if (count($items) > 1) {
            // AppHelper::sendTelegram();
            return AppHelper::returnError([
                'error' => [
                    'message' => 'Access Token duplicated',
                    'code' => PATA_ERROR_REFRESH_TOKEN_DUPLICATED
                ],
                'customData' => ['responseCode' => 401]
            ]);
        }

        // 7. burn token and refresh token with same sid
        ['data' => ['queryResult' => $deleteTokensResult]] = DbHelper::deleteToken(['sid' => $items[0]->sid ?: '']);

        // 8. generate new sdi, at, rt
        ['data' => [
            'sid' => $sid,
            'refreshToken' => $refreshToken,
            'accessToken' => $accessToken,
            'setCookieResult' => $setCookieResult,
            'tokenInsertResult' => $tokenInsertResult,
        ]] = self::generateAndSaveUserTokens(['userId' => $items[0]->user_id]);

        return AppHelper::returnSuccess(['data' => [
            'sid' => $sid,
            'refreshToken' => $refreshToken,
            'accessToken' => $accessToken,
            'debug' => [
                'setCookieResult' => $setCookieResult,
                'tokenInsertResult' => $tokenInsertResult,
                'deleteTokensResult' => $deleteTokensResult,
            ]
        ]]);
    }

    /**
     * activate()
     * Searches provided activation token and check validity then set user activated and set activation token expired
     */
    public static function activate($options = []) {
        $token = $options['token'] ?? null;

        if ($token === null) {
            return AppHelper::returnError(['error' => [
                'message' => 'Token not found',
                'code' => PATA_ERROR_ACTIVATE_TOKEN_NOTFOUND
            ]]);
        }

        //select token
        ['data' => ['items' => $items]] = DbHelper::selectActivateUserToken([
            'token' => $token
        ]);

        if (count($items) < 1) {
            return AppHelper::returnError(['error' => [
                'message' => 'Token not found',
                'code' => PATA_ERROR_ACTIVATE_TOKEN_NOTFOUND
            ]]);
        }

        if (count($items) > 1) {
            return AppHelper::returnError(['error' => [
                'message' => 'Duplicated Token',
                'code' => PATA_ERROR_ACTIVATE_DUPLICATED_TOKEN
            ]]);
        }

        ['result' => $hasExpired] = DateTimeHelper::hasExpired([
            'date' => intval($items[0]->expiration)
        ]);
        if ($hasExpired) {
            if (intval($items[0]->expiration) === intval(PATA_TOKEN_EXPIRATION_VALUE)) {
                return AppHelper::returnError(['error' => [
                    'message' => 'Token already used. Try to login.',
                    'code' => PATA_ERROR_ACTIVATE_TOKEN_USED
                ]]);
            }

            //error token expired, suggest to try login and press resend activation email
            return AppHelper::returnError(['error' => [
                'message' => 'Token Expired. Try to login and press the send activation email button',
                'code' => PATA_ERROR_ACTIVATE_TOKEN_EXPIRED
            ]]);
        }

        //ok activate user and return true
        ['data' => ['queryResult' => $queryResult]] = DbHelper::updateUser([
            'data' => ['active' => true],
            'id' => $items[0]->user_id
        ]);

        ['data' => ['queryResult' => $queryResult]] = DbHelper::updateToken([
            'data' => ['expiration' => PATA_TOKEN_EXPIRATION_VALUE],
            'id' => $items[0]->id
        ]);

        if ($queryResult === 1) {
            return AppHelper::returnSuccess(['data' => [
                'queryResult' => $queryResult,
                'userId' => $items[0]->user_id
            ]]);
        }

        return AppHelper::returnError(['error' => [
            'message' => 'Error updating token',
            'code' => PATA_ERROR_ACTIVATE_TOKEN_DB_ERROR
        ]]);
    }

    /**
     * registerUser()
     * Creates a user with given email and password then send activation email. If user already exists.
     */
    public static function registerUser($options = []) {
        $email = $options['email'] ?? '';
        $password = $options['password'] ?? '';

        if (!ValidateHelper::email(['value' => $email])) {
            return AppHelper::returnError(['error' => [
                'message' => 'Wrong email',
                'code' => PATA_ERROR_REGISTRATION_INVALID_EMAIL,
                'fields' => [['name' => 'email']]
            ]]);
        }

        if (!ValidateHelper::password(['value' => $password])) {
            return AppHelper::returnError(['error' => [
                'message' => 'Wrong password',
                'code' => PATA_ERROR_REGISTRATION_INVALID_PASSWORD,
                'fields' => [['name' => 'password']]
            ]]);
        }

        ['data' => ['items' => $users]] = DbHelper::selectUser(['email' => $email]);
        if (count($users) > 0) {
            return AppHelper::returnError(['error' => [
                'message' => 'Email already exists',
                'code' => PATA_ERROR_REGISTRATION_EMAIL_EXITSTS,
            ]]);
        }

        $created = DateTimeHelper::getMysqlUTC();

        ['result' => $result,'error' => ['message' => $message],'data' => ['id' => $id]] = DbHelper::createUser(['data' => [
            'created' => $created,
            'password' => HashHelper::hash(['value' => $password]),
            'email' => $email,
            'active' => '0',
        ]]) + ['data' => ['id' => ''], 'error' => ['message' => 'Error Inserting User in DB']];

        $activationToken = AuthHelper::generateActivateAccountToken();

        ['data' => ['queryResult' => $tokenInsertResult]] = DbHelper::createToken(['data' => [
            'created' => $created,
            'modified' => $created,
            'user_id' => $id,
            'sid' => '',
            'token' => $activationToken,
            'token_type' => PATA::$activateTokenName,
            'expiration' => DateTimeHelper::getActivateAccountTokenExpiration(['date' => $created]),
        ]]);

        if (!$result) {
            return AppHelper::returnError(['error' => [
                'message' => $message,
                'code' => PATA_ERROR_REGISTRATION_CREATE,
                'fields' => ['id' => $id]
            ]]);
        }

        return AppHelper::returnSuccess(['data' => [
            'id' => $id,
            'shouldSendActivationEmail' => true,
            'activationToken' => $activationToken,
        ]]);
    }

    /**
     * loginUser()
     * Check provided credentials then create a user session with refresh token, access token and session id. If provided credentials are wrong or usr isn't activated return an error
     */
    public static function loginUser($options = []) {
        $email = $options['email'] ?? '';
        $password = $options['password'] ?? '';

        if (!ValidateHelper::email(['value' => $email])) {
            return AppHelper::returnError(['error' => [
                'message' => 'Wrong email',
                'code' => PATA_ERROR_LOGIN_INVALID_EMAIL,
                'fields' => [['name' => 'email']]
            ]]);
        }

        if (!ValidateHelper::password(['value' => $password])) {
            return AppHelper::returnError(['error' => [
                'message' => 'Wrong password',
                'code' => PATA_ERROR_LOGIN_INVALID_PASSWORD,
                'fields' => [['name' => 'password']]
            ]]);
        }

        $encryptedPassword = HashHelper::hash(['value' => $password]);
        ['data' => ['items' => $users]] = DbHelper::selectUser([
            'email' => $email
        ]);

        if (count($users) <= 0) {
            return AppHelper::returnError(['error' => [
                'message' => 'Wrong email',
                'code' => PATA_ERROR_WRONG_EMAIL,
            ]]);
        }

        if (!HashHelper::hashCheck(['value' => $password, 'hashedValue' => $users[0]->password])) {
            return AppHelper::returnError(['error' => [
                'message' => 'Wrong password',
                'code' => PATA_ERROR_WRONG_PASSWORD,
            ]]);
        }

        if (!$users[0]->active) {
            return AppHelper::returnError(['error' => [
                'message' => 'User not active',
                'code' => PATA_ERROR_USER_NOT_ACTIVE,
            ]]);
        }

        ['data' => [
            'sid' => $sid,
            'refreshToken' => $refreshToken,
            'accessToken' => $accessToken,
            'setCookieResult' => $setCookieResult,
            'tokenInsertResult' => $tokenInsertResult,
        ]] = self::generateAndSaveUserTokens(['userId' => $users[0]->id]);

        return AppHelper::returnSuccess([
            'data' => [
                'user' => $users[0],
                'accessToken' => $accessToken,
                'sid' => $sid,
                'debug' => [
                    'rtResult' => $setCookieResult,
                    'tokenInsertResult' => $tokenInsertResult
                ],
            ]
        ]);
    }

    /**
     * logoutUser()
     * First executes authenticate() to check accessToken then delete user tokens associated to a specific sid
     */
    public static function logoutUser($options = []) {
        $accessToken = $options['accessToken'] ?? null;

        $authResult = self::authenticate(['accessToken' => $accessToken]);

        if (!$authResult['result']) {
            return AppHelper::returnError([
                'error' => $authResult['error'] ?? []
            ]);
        }

        ['data' => ['sid' => $sid]] = $authResult;

        //deleteToken uses AppHelper::returnSuccess
        return DbHelper::deleteToken(['sid' => $sid ?: '']);
    }

    /**
     * forgotPassword()
     * Check if email exists then send email with change password link (only if user is activated)
     * 1. check email is valid
     * 2. find active user
     * 3. find change password tokens
     *  3.1 if expired, delete it
     *  3.2 if not expired return error
     */
    public static function forgotPassword($options = []) {
        $email = $options['email'] ?? '';

        // 1. check email is valid
        if (!ValidateHelper::email(['value' => $email])) {
            return AppHelper::returnError(['error' => [
                'message' => 'Wrong email',
                'code' => PATA_ERROR_FORGOT_PASSWORD_INVALID_EMAIL,
            ]]);
        }

        // 2. find active user
        ['data' => ['items' => $users]] = DbHelper::selectUser([
            'email' => $email
        ]);

        if (count($users) <= 0) {
            return AppHelper::returnError(['error' => [
                'message' => 'Wrong email',
                'code' => PATA_ERROR_FORGOT_PASSWORD_INVALID_EMAIL,
            ]]);
        }

        if (!$users[0]->active) {
            return AppHelper::returnError(['error' => [
                'message' => 'User not active',
                'code' => PATA_ERROR_FORGOT_PASSWORD_USER_NOT_ACTIVE,
            ]]);
        }

        $userId = $users[0]->id;

        // 3. find change password tokens
        ['data' => ['items' => $tokens]] = DbHelper::selectChangePasswordToken([
            'userId' => $userId,
        ]);

        if (count($tokens) > 0) {
            //check expiration
            ['result' => $hasExpired, 'diff' => $diff] = DateTimeHelper::hasExpired([
                'date' => intval($tokens[0]->expiration)
            ]);
            if ($hasExpired) {
                DbHelper::deleteToken(['token' => $tokens[0]->token ?: '']);
            } else {
                // 3.2 if not expired return error
                return AppHelper::returnError([
                    'error' => [
                        'message' => 'Already present',
                        'code' => PATA_ERROR_FORGOT_PASSWORD_ALREADY_PRESENT,
                    ],
                    'customData' => ['secondsLeft' => $diff]
                ]);
            }
        }

        // generate changePasswordTokenName
        $changePasswordToken = AuthHelper::generateChangePasswordToken();
        $created = DateTimeHelper::getMysqlUTC();
        ['data' => ['queryResult' => $tokenInsertResult]] = DbHelper::createToken(['data' => [
            'created' => $created,
            'modified' => $created,
            'user_id' => $users[0]->id,
            'sid' => '',
            'token' => $changePasswordToken,
            'token_type' => PATA::$changePasswordTokenName,
            'expiration' => DateTimeHelper::getChangePasswordTokenExpiration(['date' => $created]),
        ]]);

        return AppHelper::returnSuccess(['data' => [
            'changePasswordToken' => $changePasswordToken,
            'shouldSendChangePasswordEmail' => true,
            'queryResult' => $tokenInsertResult]
        ]);
    }

    /**
     * changePassword(password, token)
     * Check if password and token are valid then burn token and change password of the associated user (only if user is activated)
     * 1. check password is valid
     * 2. check token is valid and not expired
     * 3. check user is active
     * 4. check password is changed
     * 5. change password in db
     * 6. delete current token + all access token + all refresh token
     */
    public static function changePassword($options = []) {
        $password = $options['password'] ?? '';
        $token = $options['token'] ?? '';

        // 1. check password is valid
        if (!ValidateHelper::password(['value' => $password])) {
            return AppHelper::returnError(['error' => [
                'message' => 'Wrong password',
                'code' => PATA_ERROR_CHANGE_PASSWORD_INVALID_PASSWORD,
            ]]);
        }

        // 2. check token is valid and not expired
        if (!ValidateHelper::changePasswordToken(['value' => $token])) {
            return AppHelper::returnError(['error' => [
                'message' => 'Token not valid',
                'code' => PATA_ERROR_CHANGE_PASSWORD_INVALID_TOKEN,
            ]]);
        }

        ['data' => ['items' => $tokens]] = DbHelper::selectChangePasswordToken([
            'token' => $token,
        ]);

        if (count($tokens) === 0) {
            return AppHelper::returnError(['error' => [
                'message' => 'Token not found',
                'code' => PATA_ERROR_CHANGE_PASSWORD_TOKEN_NOT_FOUND,
            ]]);
        }

        ['result' => $hasExpired] = DateTimeHelper::hasExpired([
            'date' => intval($tokens[0]->expiration)
        ]);
        if ($hasExpired) {
            DbHelper::deleteToken(['token' => $tokens[0]->token ?: '']);
            return AppHelper::returnError(['error' => [
                'message' => 'Token expired',
                'code' => PATA_ERROR_CHANGE_PASSWORD_TOKEN_EXPIRED,
            ]]);
        }

        // 3. check user is active
        ['data' => ['items' => $users]] = DbHelper::selectUser([
            'id' => $tokens[0]->user_id,
        ]);

        if (count($users) === 0) {
            return AppHelper::returnError(['error' => [
                'message' => 'User not found',
                'code' => PATA_ERROR_CHANGE_PASSWORD_USER_NOT_FOUND,
            ]]);
        }

        if (!$users[0]->active) {
            return AppHelper::returnError(['error' => [
                'message' => 'User not active',
                'code' => PATA_ERROR_CHANGE_PASSWORD_USER_NOT_ACTIVE,
            ]]);
        }

        // 4. check password is changed
        if (
            count($users) > 0
            && HashHelper::hashCheck(['value' => $password, 'hashedValue' => $users[0]->password])
        ) {
            return AppHelper::returnError(['error' => [
                'message' => 'Password not changed',
                'code' => PATA_ERROR_CHANGE_PASSWORD_PASSWORD_NOT_CHANGED,
            ]]);
        }

        // 5. change password in db
        ['data' => ['queryResult' => $queryResult]] = DbHelper::updateUser([
            'data' => ['password' => HashHelper::hash(['value' => $password])],
            'id' => $tokens[0]->user_id
        ]);

        if ($queryResult === 1) {
            //6. delete current token
            ['data' => ['queryResult' => $currentTokenDeleted]] = DbHelper::deleteToken(['token' => $tokens[0]->token ?: '']);
            //6. delete all access token
            ['data' => ['queryResult' => $accessTokenDeleted]] = DbHelper::deleteToken([
                'userId' => $tokens[0]->user_id ?: '',
                'type' => PATA::$accessTokenName
            ]);
            //6. delete all refresh token
            ['data' => ['queryResult' => $refreshTokenDeleted]] = DbHelper::deleteToken([
                'userId' => $tokens[0]->user_id ?: '',
                'type' => PATA::$refreshTokenName
            ]);

            return AppHelper::returnSuccess(['data' => [
                'queryResult' => $queryResult,
                'currentTokenDeleted' => $currentTokenDeleted,
                'accessTokenDeleted' => $accessTokenDeleted,
                'refreshTokenDeleted' => $refreshTokenDeleted,
                'email' => $users[0]->email,
                'userId' => $users[0]->id,
            ]]);
        }

        return AppHelper::returnError(['error' => [
            'message' => 'Error updating user',
            'code' => PATA_ERROR_CHANGE_PASSWORD_UPDATE_USER
        ]]);
    }

    public static function generateAndSaveUserTokens($options = []) {
        $userId = $options['userId'] ?? '';

        //generate access token
        $accessToken = self::generateAccessToken();

        //generate refresh token
        $refreshToken = self::generateRefreshToken();

        //generate secure identifier
        $sid = self::generateSid();

        //save on db
        $created = DateTimeHelper::getMysqlUTC();
        $baseInsert = ['created' => $created, 'modified' => $created, 'user_id' => $userId, 'sid' => $sid];
        ['data' => ['queryResult' => $tokenInsertResult]] = DbHelper::createToken(['data' => [
            $baseInsert + [
                'token' => $accessToken,
                'token_type' => PATA::$accessTokenName,
                'expiration' => DateTimeHelper::getAccessTokenExpiration(['date' => $created]),
            ],
            $baseInsert + [
                'token' => $refreshToken,
                'token_type' => PATA::$refreshTokenName,
                'expiration' => DateTimeHelper::getRefreshTokenExpiration(),
            ],
        ]]);

        // @todo DELETE FROM `users_app_tokens` ORDER BY `created_at` DESC limit ($numAccessToken-10)
        // ["items" => $numAccessToken] = DbHelper::selectAccessToken(["count" => true, "userId" => $users[0]->id]);
        // if($numAccessToken>10){}

        //send user refresh token in httpOnly,secure and path="/auth/refresh-token"
        ['result' => $setCookieRes] = self::setRefreshTokenCookie(['rt' => $refreshToken]);

        return AppHelper::returnSuccess(['data' => [
            'sid' => $sid,
            'refreshToken' => $refreshToken,
            'accessToken' => $accessToken,
            'setCookieResult' => $setCookieRes,
            'tokenInsertResult' => $tokenInsertResult,
        ]]);
    }

    public static function generateAccessToken($options = []) {
        return self::generateToken();
    }

    public static function generateRefreshToken($options = []) {
        return self::generateToken(['length' => 32]);
    }

    public static function generateSid($options = []) {
        return self::generateToken(['length' => 16]);
    }

    public static function generateActivateAccountToken($options = []) {
        return self::generateToken(['length' => 32]);
    }

    public static function generateChangePasswordToken($options = []) {
        return self::generateToken(['length' => 32]);
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
        $rt = $options['rt'] ?? '';

        // this check is for phpunit
        if (headers_sent() !== false) {
            return AppHelper::returnError();
        }

        $res = setcookie(
            PATA::$cookieRefreshTokenName, //name
          $rt, //value
          time() + PATA_REFRESH_TOKEN_TOKEN_DURATION, //expires_or_options
          PATA::$endpointRefreshToken, //path
          PATA::$domainRefreshToken, //domain
          true, //secure
          true, //httponly
        );

        if ($res) {
            AppHelper::returnSuccess();
        }

        AppHelper::returnError();
    }
}
