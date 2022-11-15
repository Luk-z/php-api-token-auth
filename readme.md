# Php Api Token Authentication

This library is based on https://www.yiiframework.com/wiki/2568/jwt-authentication-tutorial

## Functions

### Authenticate

Required params:

-   access token: ca be passed as headers field or GET/POST param (default name 'at')

Success:

```php
[
    "result" => true,
    "data" => [...]
]
```

Errors:

-   PAJA_ERROR_AUTH_INVALID_TOKEN
-   PAJA_ERROR_AUTH_TOKEN_NOT_FOUND
-   PAJA_ERROR_AUTH_TOKEN_DUPLICATED
-   PAJA_ERROR_AUTH_TOKEN_EXPIRED

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

### Refresh Token

Required params:

-   refresh token: must be passed as cookie (default name 'rn_rt')

Success:

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

Errors:

-   ... all error codes returnd by Authenticate
-   PAJA_ERROR_REFRESH_TOKEN_INVALID
-   PAJA_ERROR_REFRESH_TOKEN_NOT_FOUND
-   PAJA_ERROR_REFRESH_TOKEN_EXPIRED
-   PAJA_ERROR_REFRESH_TOKEN_DIFFERENT_SID
-   PAJA_ERROR_REFRESH_TOKEN_DUPLICATED

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
