<?php

// define("PATA_CURRENT_HOST", $_SERVER['HTTP_HOST']);
// define("PATA_CURRENT_SCHEME", "https");
// define("PATA_CURRENT_DOMAIN", PATA_CURRENT_SCHEME."://".PATA_CURRENT_HOST."/");

define("PATA_HELPERS_PATH", "Helpers");
define("PATA_DB_PATH", "Db");
define("PATA_SECURITY_PATH", "Security");

define("PATA_TOKEN_TYPE_ACTIVATE_ACCOUNT", 'act');

//ERROR CODES
define("PATA_ERROR_REGISTRATION_EMAIL_EXITSTS", "registration_email_exitsts");
define("PATA_ERROR_REGISTRATION_CREATE", "registration_create");

define("PATA_ERROR_AUTH_INVALID_TOKEN", "auth_invalid_token");
define("PATA_ERROR_AUTH_TOKEN_NOT_FOUND", "auth_token_not_found");
define("PATA_ERROR_AUTH_TOKEN_DUPLICATED", "auth_token_duplicated");
define("PATA_ERROR_AUTH_TOKEN_EXPIRED", "auth_token_expired");
define("PATA_ERROR_WRONG_EMAIL", "wrong_email");
define("PATA_ERROR_WRONG_PASSWORD", "wrong_password");
define("PATA_ERROR_USER_NOT_ACTIVE", "user_not_active");


define("PATA_ERROR_REFRESH_TOKEN_INVALID", "rt_invalid_token");
define("PATA_ERROR_REFRESH_TOKEN_NOT_FOUND", "rt_token_not_found");
define("PATA_ERROR_REFRESH_TOKEN_DUPLICATED", "rt_token_duplicated");
define("PATA_ERROR_REFRESH_TOKEN_EXPIRED", "rt_token_expired");
define("PATA_ERROR_REFRESH_TOKEN_DIFFERENT_SID", "rt_token_diff_sid");

define("PATA_ERROR_ACTIVATE_TOKEN_DB_ERROR", "activate_token_db_error");
define("PATA_ERROR_ACTIVATE_TOKEN_EXPIRED", "activate_token_expired");
define("PATA_ERROR_ACTIVATE_TOKEN_USED", "activate_token_used");
define("PATA_ERROR_ACTIVATE_DUPLICATED_TOKEN", "activate_duplicated_token");
define("PATA_ERROR_ACTIVATE_TOKEN_NOTFOUND", "activate_token_notfound");
define("PATA_ERROR_ACTIVATE_EMPTY_TOKEN", "activate_empty_token");

//DATE FORMAT
define("PATA_DATE_FORMAT_MYSQL", "Y-m-d H:i:s");
define("PATA_TOKEN_EXPIRATION_VALUE", "-1"); // used to force token expiration
define("PATA_TOKEN_EXPIRATION_VALUE_INFINITE", 0); // token will not expire

//TOKEN EXPIRATION (in seconds)
define("PATA_ACCESS_TOKEN_DURATION", 60*60*24); // 1 day
// define("PATA_ACTIVATE_ACCOUNT_TOKEN_DURATION", 60*60*24*2); // 2 days