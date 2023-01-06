# Php Api Token Authentication

This library is based on https://www.yiiframework.com/wiki/2568/jwt-authentication-tutorial

## Install

### Composer

```shell
composer require luk-z/php-api-token-auth
```

### Manual

Donwload ad extract source code from github, then include in you project:

```php
require_once SDIM_LIB_PATA_DIR.'/index.php';
```

## Run test

```shell
vendor/bin/phpunit
```

## TODO

- https://phpstan.org/ see https://github.com/firebase/php-jwt/blob/main/.github/workflows/tests.yml
- changelog
- .editorconfig https://github.com/kreait/firebase-php https://github.com/cakephp/cakephp

## PHP CS Fixer

To use correctly PHP CS Fixer copy `settings.json-example` to `settings.json` and insert absolute path of `tools/php-cs-fixer/vendor/bin/php-cs-fixer` to `php-cs-fixer.executablePath`

## Release

Repository is linked to packagist through (github web hook)[https://packagist.org/about#how-to-update-packages]. To push an update simply push a tag.

```shell
git tag v1.0.0 && git push origin v1.0.0
```

## Functions

### PATA::init()

Initialize the library passing dome configuration information.

Params: TODO

Returns: void

### PATA::authenticate()

Take an access token and check if is valid/not expired

Params:

- `string` accessToken (required)
- `bool` checkExpired (optional): default to `true`

Returns:

- Success response

```php
[
    "result" => true,
    "data" => ["sid" => string] // user session id
]
```

- Error response:

```php
[
    "result" => false,
    "error" => [
        "message" => string,
        "code" => string,
        "fields" => array,
    ]
]
```

- Error codes:
  - PATA_ERROR_AUTH_INVALID_TOKEN
  - PATA_ERROR_AUTH_TOKEN_NOT_FOUND
  - PATA_ERROR_AUTH_TOKEN_DUPLICATED
  - PATA_ERROR_AUTH_TOKEN_EXPIRED

### PATA::refreshToken()

Takes an access token and refresh token and try to refresh a new access token. If refreshToken not passed try to get from cookies

Params:

- `string` accessToken (required)
- `string` refreshToken (required)

Returns:

- Success response

```php
[
    "result" => true,
    "data" => [
        "sid" => string,
        "refreshToken" => string,
        "accessToken" => string,
        "debug" => [
            "setCookieResult" => string,
            "tokenInsertResult" => string,
            "deleteTokensResult" => string,
        ],
    ]
]
```

- Error response:

```php
[
    "result" => false,
    "error" => [
        "message" => string,
        "code" => string,
        "fields" => array,
    ],
    "responseCode" => string, // suggested response code to return by endpoints
]
```

- Error codes:
  - ... all error codes returned by Authenticate
  - PATA_ERROR_REFRESH_TOKEN_INVALID - suggested response code=422
  - PATA_ERROR_REFRESH_TOKEN_NOT_FOUND - suggested response code=401
  - PATA_ERROR_REFRESH_TOKEN_EXPIRED - suggested response code=401
  - PATA_ERROR_REFRESH_TOKEN_DIFFERENT_SID - suggested response code=401
  - PATA_ERROR_REFRESH_TOKEN_DUPLICATED - suggested response code=401

### PATA::activate()

Searches provided activation token and check validity then set user activated and set activation token expired

Params:

- `string` accessToken (required)

Returns:

- Success response

```php
[
    "result" => true,
    "data" => [
        "queryResult" => int, // affected row (should be 1)
    ]
]
```

- Error response:

```php
[
    "result" => false,
    "error" => [
        "message" => string,
        "code" => string,
    ],
]
```

- Error codes:
  - PATA_ERROR_ACTIVATE_TOKEN_NOTFOUND
  - PATA_ERROR_ACTIVATE_DUPLICATED_TOKEN
  - PATA_ERROR_ACTIVATE_TOKEN_USED
  - PATA_ERROR_ACTIVATE_TOKEN_EXPIRED
  - PATA_TOKEN_EXPIRATION_VALUE
  - PATA_ERROR_ACTIVATE_TOKEN_DB_ERROR

### PATA::registerUser()

Creates a user with given email and password then send activation email. If user already exists.

Params:

- `string` email (required)
- `string` password (required)

Returns:

- Success response

```php
[
    "result" => true,
    "data" => [
        "id" => int, // userId
        "shouldSendActivationEmail" => bool, // whether an activation email should be sent
        "activationToken" => "xxxxx", // user token for account activation
    ]
]
```

- Error response:

```php
[
    "result" => false,
    "error" => [
        "message" => string,
        "code" => string,
        "fields" => ["id"=>int], // userId
    ],
]
```

- Error codes:
  - PATA_ERROR_REGISTRATION_INVALID_EMAIL
  - PATA_ERROR_REGISTRATION_INVALID_PASSWORD
  - PATA_ERROR_REGISTRATION_EMAIL_EXITSTS
  - PATA_ERROR_REGISTRATION_CREATE

### PATA::loginUser()

Check provided credentials then create a user session with refresh token, access token and session id. If provided credentials are wrong or usr isn't activated return an error

Params:

- `string` email (required)
- `string` password (required)

Returns:

- Success response

```php
[
    "result" => true,
    "data"=>[
        "user" => array,
        "accessToken" => string,
        "sid" => string,
        "debug" => [
            "rtResult" => bool, // whether the set_cookie has succedeed
            "tokenInsertResult" => bool // whether the token is succesfully created in the database
        ],
    ]
]
```

- Error response:

```php
[
    "result" => false,
    "error" => [
        "message" => string,
        "code" => string,
    ],
]
```

- Error codes:
  - PATA_ERROR_LOGIN_INVALID_EMAIL
  - PATA_ERROR_LOGIN_INVALID_PASSWORD
  - PATA_ERROR_WRONG_EMAIL
  - PATA_ERROR_WRONG_PASSWORD
  - PATA_ERROR_USER_NOT_ACTIVE

### PATA::logoutUser()

First executes authenticate() to check accessToken then delete user tokens associated to a specific sid

Params:

- `string` sid (required)
- `accessToken` accessToken (required)

Returns:

- Success response

```php
[
    "result" => true,
    "data" => [
        "queryResult" => int, // number of user session tokens deleted
    ]
]
```

- Error response:

```php
[
    "result" => false,
    "error" => [
        "message" => string,
        "code" => string,
    ],
]
```

- Error codes:
  - ... all error codes returned by Authenticate

### PATA::forgotPassword()

Check if email exists then send email with change password link:

1. check email is valid
2. find user
3. find change password tokens
   3.1 if expired, delete it
   3.2 if not expired return error

Params:

- `string` email (required)

Returns:

- Success response

```php
[
    "result" => true,
    "data"=>[
        "changePasswordToken" => string,
        "shouldSendChangePasswordEmail" => string,
        "queryResult" => int,
    ]
]
```

- Error response:

```php
[
    "result" => false,
    "error" => [
        "message" => string,
        "code" => string,
    ],
    "secondsLeft" => int // only if a valid token is already present, indicates the remaining seconds till token expiration
]
```

- Error codes:
  - PATA_ERROR_FORGOT_PASSWORD_INVALID_EMAIL
  - PATA_ERROR_FORGOT_PASSWORD_ALREADY_PRESENT

## Developing

### Install php-cs-fixer

```shell
composer require --working-dir=tools/php-cs-fixer friendsofphp/php-cs-fixer
```

Usefull guides:

- https://github.com/PHP-CS-Fixer/PHP-CS-Fixer
- https://github.com/junstyle/vscode-php-cs-fixer
- https://medium.com/@armorasha/php-cs-fixer-how-to-install-vs-code-2020-windows-10-75b6d5ed03ce
