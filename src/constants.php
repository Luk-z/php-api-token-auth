<?php

// define("PATA_CURRENT_HOST", $_SERVER['HTTP_HOST']);
// define("PATA_CURRENT_SCHEME", "https");
// define("PATA_CURRENT_DOMAIN", PATA_CURRENT_SCHEME."://".PATA_CURRENT_HOST."/");

define('PATA_DEFAULT_USERS_TABLE_NAME', 'users');
define('PATA_DEFAULT_TOKENS_TABLE_NAME', 'tokens');
define('PATA_DEFAULT_ACCESS_TOKEN', 'at');
define('PATA_DEFAULT_REFRESH_TOKEN', 'rt');
define('PATA_DEFAULT_ACTIVATE_TOKEN', 'act');
define('PATA_DEFAULT_CHANGE_PASSWORD_TOKEN', 'change_psw');
define('PATA_DEFAULT_COOKIE_REFRESH_TOKEN_NAME', 'rn_rt');
define('PATA_DEFAULT_ENDPOINT_REFRESH_TOKEN', '/auth/refresh-token');
define('PATA_DEFAULT_DOMAIN_REFRESH_TOKEN', 'api-develop.ronchesisrl.it');

define('PATA_HELPERS_PATH', 'Helpers');
define('PATA_DB_PATH', 'Db');
define('PATA_SECURITY_PATH', 'Security');

define('PATA_TOKEN_TYPE_ACTIVATE_ACCOUNT', 'act');

//ERROR CODES
define('PATA_ERROR_REGISTRATION_INVALID_EMAIL', 'registration_invalid_email');
define('PATA_ERROR_REGISTRATION_INVALID_PASSWORD', 'registration_invalid_password');
define('PATA_ERROR_REGISTRATION_EMAIL_EXITSTS', 'registration_email_exitsts');
define('PATA_ERROR_REGISTRATION_CREATE', 'registration_create');

define('PATA_ERROR_AUTH_INVALID_TOKEN', 'auth_invalid_token');
define('PATA_ERROR_AUTH_TOKEN_NOT_FOUND', 'auth_token_not_found');
define('PATA_ERROR_AUTH_TOKEN_DUPLICATED', 'auth_token_duplicated');
define('PATA_ERROR_AUTH_TOKEN_EXPIRED', 'auth_token_expired');
define('PATA_ERROR_LOGIN_INVALID_EMAIL', 'login_invalid_email');
define('PATA_ERROR_LOGIN_INVALID_PASSWORD', 'login_invalid_password');
define('PATA_ERROR_WRONG_EMAIL', 'wrong_email');
define('PATA_ERROR_WRONG_PASSWORD', 'wrong_password');
define('PATA_ERROR_USER_NOT_ACTIVE', 'user_not_active');

define('PATA_ERROR_REFRESH_TOKEN_INVALID', 'rt_invalid_token');
define('PATA_ERROR_REFRESH_TOKEN_NOT_FOUND', 'rt_token_not_found');
define('PATA_ERROR_REFRESH_TOKEN_DUPLICATED', 'rt_token_duplicated');
define('PATA_ERROR_REFRESH_TOKEN_EXPIRED', 'rt_token_expired');
define('PATA_ERROR_REFRESH_TOKEN_DIFFERENT_SID', 'rt_token_diff_sid');

define('PATA_ERROR_ACTIVATE_TOKEN_DB_ERROR', 'activate_token_db_error');
define('PATA_ERROR_ACTIVATE_TOKEN_EXPIRED', 'activate_token_expired');
define('PATA_ERROR_ACTIVATE_TOKEN_USED', 'activate_token_used');
define('PATA_ERROR_ACTIVATE_DUPLICATED_TOKEN', 'activate_duplicated_token');
define('PATA_ERROR_ACTIVATE_TOKEN_NOTFOUND', 'activate_token_notfound');
define('PATA_ERROR_ACTIVATE_EMPTY_TOKEN', 'activate_empty_token');

define('PATA_ERROR_FORGOT_PASSWORD_INVALID_EMAIL', 'forgot_password_invalid_email');
define('PATA_ERROR_FORGOT_PASSWORD_ALREADY_PRESENT', 'forgot_password_already_present');

define('PATA_ERROR_CHANGE_PASSWORD_INVALID_PASSWORD', 'change_password_invalid_password');
define('PATA_ERROR_CHANGE_PASSWORD_INVALID_TOKEN', 'change_password_invalid_token');
define('PATA_ERROR_CHANGE_PASSWORD_TOKEN_NOT_FOUND', 'change_password_token_not_found');
define('PATA_ERROR_CHANGE_PASSWORD_TOKEN_EXPIRED', 'change_password_token_expired');
define('PATA_ERROR_CHANGE_PASSWORD_PASSWORD_NOT_CHANGED', 'change_password_password_not_changed');
define('PATA_ERROR_CHANGE_PASSWORD_UPDATE_USER', 'change_password_update_user');

//DATE FORMAT
define('PATA_DATE_FORMAT_MYSQL', 'Y-m-d H:i:s');
define('PATA_TOKEN_EXPIRATION_VALUE', '-1'); // used to force token expiration
define('PATA_TOKEN_EXPIRATION_VALUE_INFINITE', 0); // token will not expire

//TOKEN EXPIRATION (in seconds)
define('PATA_ACCESS_TOKEN_DURATION', 60 * 60 * 24); // 1 day
define('PATA_ACTIVATE_ACCOUNT_TOKEN_DURATION', 60 * 60 * 24); // 1 day
define('PATA_CHANGE_PASSWORD_TOKEN_DURATION', 60 * 15); // 15 min
// define("PATA_ACTIVATE_ACCOUNT_TOKEN_DURATION", 60*60*24*2); // 2 days

//REGEX
define('PATA_REGEX_PASSWORD', "/^(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])(?=\S*[\W])\S*$/");
