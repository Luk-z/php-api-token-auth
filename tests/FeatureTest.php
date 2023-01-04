<?php

use PHPUnit\Framework\TestCase;
use PATA\PATA;
use PATA\Helpers\DbHelper;

final class FeatureTest extends TestCase {
    public static function mockTokenData() {
        return [
            'created' => '2022-01-01 12:00:00',
            'modified' => '2022-01-01 12:00:00',
            'user_id' => 1,
            'sid' => 'sid1',
            'token' => 'token1',
            'token_type' => PATA::$accessTokenName,
            'expiration' => 9999999999999,
        ];
    }

    public static function mockUserData() {
        return [
            'created' => '2022-01-01 12:00:00',
            'email' => 'test@test.it',
            'password' => 'Test123!p1',
            'active' => 0,
        ];
    }

    public static function populateTokens() {
        $originalData = self::mockTokenData();

        $item = $originalData;
        $res = DbHelper::createToken(['data' => $item]);

        $item = ['token' => 'token2', 'sid' => 'sid2'] + $originalData;
        $res = DbHelper::createToken(['data' => $item]);

        $item = ['token' => 'token3', 'sid' => 'sid3'] + $originalData;
        $res = DbHelper::createToken(['data' => $item]);

        $item = ['token' => 'token4', 'sid' => 'sid4', 'expiration' => 1111] + $originalData;
        $res = DbHelper::createToken(['data' => $item]);

        $item = [
            'token' => 'refreshToken3',
            'sid' => 'sid3',
            'expiration' => 1111,
            'token_type' => PATA::$refreshTokenName
        ] + $originalData;
        $res = DbHelper::createToken(['data' => $item]);

        $item = [
            'token' => 'refreshToken2a',
            'sid' => 'sid2wrong',
            'token_type' => PATA::$refreshTokenName
        ] + $originalData;
        $res = DbHelper::createToken(['data' => $item]);

        $item = [
            'token' => 'refreshToken2b',
            'sid' => 'sid2',
            'token_type' => PATA::$refreshTokenName
        ] + $originalData;
        $res = DbHelper::createToken(['data' => $item]);

        $item = [
            'token' => 'activate1',
            'sid' => 'sidActivate1Expired',
            'token_type' => PATA::$activateTokenName,
            'expiration' => 1111,
            'user_id' => 2,
        ] + $originalData;
        $res = DbHelper::createToken(['data' => $item]);

        $item = [
            'token' => 'activate2',
            'sid' => 'sidActivate2',
            'user_id' => 3,
            'token_type' => PATA::$activateTokenName,
        ] + $originalData;
        $res = DbHelper::createToken(['data' => $item]);
    }

    public static function populateUsers() {
        $originalData = self::mockUserData();

        $item = $originalData;
        $res = DbHelper::createUser(['data' => $item]);

        $item = ['email' => 'test2@test.it', 'password' => 'Test123!p2'] + $originalData;
        $res = DbHelper::createUser(['data' => $item]);

        $item = ['email' => 'test3@test.it', 'password' => 'Test123!p3'] + $originalData;
        $res = DbHelper::createUser(['data' => $item]);
    }

    public static function populate() {
        self::populateTokens();
        self::populateUsers();
    }

    public function testAuthenticate() {
        DbTest::initDb();

        self::populate();

        $res = PATA::authenticate();
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_AUTH_INVALID_TOKEN);

        $res = PATA::authenticate(['accessToken' => 'none']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_AUTH_TOKEN_NOT_FOUND);

        $res = PATA::authenticate(['accessToken' => 'token4', 'checkExpired' => false]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['sid'], 'sid4');

        $res = PATA::authenticate(['accessToken' => 'token2']);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['sid'], 'sid2');
        $this->assertEquals($res['data']['userId'], '1');

        $res = PATA::authenticate(['accessToken' => 'token4']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_AUTH_TOKEN_EXPIRED);
    }

    public function testRefreshToken() {
        $res = PATA::refreshToken();
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_REFRESH_TOKEN_INVALID);

        $res = PATA::refreshToken(['refreshToken' => 'aaa']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_AUTH_INVALID_TOKEN);

        $res = PATA::refreshToken(['refreshToken' => 'aaa', 'accessToken' => 'bbb']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_AUTH_TOKEN_NOT_FOUND);

        $res = PATA::refreshToken(['refreshToken' => 'aaa', 'accessToken' => 'token2']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_REFRESH_TOKEN_NOT_FOUND);

        $res = PATA::refreshToken(['refreshToken' => 'refreshToken2a', 'accessToken' => 'token2']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_REFRESH_TOKEN_DIFFERENT_SID);

        $res = PATA::refreshToken(['refreshToken' => 'refreshToken2b', 'accessToken' => 'token2']);
        $this->assertEquals($res['result'], true);
        $newSid = $res['data']['sid'];
        $newAccessToken = $res['data']['accessToken'];
        $newRrefreshToken = $res['data']['refreshToken'];

        $res = PATA::authenticate(['accessToken' => $newAccessToken]); //todo
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['sid'], $newSid);

        $res = PATA::refreshToken(['refreshToken' => $newRrefreshToken, 'accessToken' => $newAccessToken]);
        $this->assertEquals($res['result'], true);

        $res = PATA::refreshToken(['refreshToken' => 'refreshToken3', 'accessToken' => 'token3']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_REFRESH_TOKEN_EXPIRED);
    }

    public function testActivate() {
        $res = PATA::activate();
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_ACTIVATE_TOKEN_NOTFOUND);

        $res = PATA::activate(['token' => 'qwerty']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_ACTIVATE_TOKEN_NOTFOUND);

        $res = PATA::activate(['token' => 'activate1']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_ACTIVATE_TOKEN_EXPIRED);

        $res = PATA::activate(['token' => 'activate2']);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['queryResult'], 1);

        $res = DbHelper::selectUser(['email' => 'test3@test.it']);
        $this->assertEquals(count($res['data']['items']), 1);
        $this->assertEquals($res['data']['items'][0]->active, true);
    }

    public function testRegisterUser() {
        $res = PATA::registerUser();
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_REGISTRATION_INVALID_EMAIL);

        $res = PATA::registerUser(['email' => 'qwerty', 'password' => 'Test123!']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_REGISTRATION_INVALID_EMAIL);

        $res = PATA::registerUser(['email' => 'test1@test.it']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_REGISTRATION_INVALID_PASSWORD);

        $res = PATA::registerUser(['email' => 'test1@test.it', 'password' => 'fook']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_REGISTRATION_INVALID_PASSWORD);

        $res = PATA::registerUser(['email' => 'test2@test.it', 'password' => 'Test123!']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_REGISTRATION_EMAIL_EXITSTS);

        $res = PATA::registerUser(['email' => 'test.registration1@test.it', 'password' => 'Test123!']);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['shouldSendActivationEmail'], true);
        $this->assertEquals($res['data']['id'] > 0, true);
        $this->assertEquals(!!$res['data']['activationToken'], true);

        $registeredId = $res['data']['id'];

        $res = DbHelper::selectUser(['email' => 'test.registration1@test.it']);
        $this->assertEquals(count($res['data']['items']), 1);
        $this->assertEquals($res['data']['items'][0]->active, false);

        $res = DbHelper::selectActivateUserToken(['userId' => $registeredId]);
        $this->assertEquals(count($res['data']['items']), 1);
    }

    public function testLoginUser() {
        $res = PATA::loginUser();
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_LOGIN_INVALID_EMAIL);

        $res = PATA::loginUser(['email' => 'wwww', 'password' => 'Test123!']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_LOGIN_INVALID_EMAIL);

        $res = PATA::loginUser(['email' => 'testNotFound@test.it']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_LOGIN_INVALID_PASSWORD);

        $res = PATA::loginUser(['email' => 'testNotFound@test.it', 'password' => 'aaa']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_LOGIN_INVALID_PASSWORD);

        $res = PATA::loginUser(['email' => 'testNotFound@test.it', 'password' => 'Test123!']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_WRONG_EMAIL);

        $res = PATA::loginUser(['email' => 'test@test.it', 'password' => 'Test123!xxx']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_WRONG_PASSWORD);

        $res = PATA::loginUser(['email' => 'test@test.it', 'password' => 'Test123!p1']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_USER_NOT_ACTIVE);

        $res = PATA::loginUser(['email' => 'test3@test.it', 'password' => 'Test123!p3']);
        $this->assertEquals($res['result'], true);
        $sid = $res['data']['sid'];
        $accessToken = $res['data']['accessToken'];

        $res = DbHelper::selectAccessToken(['sid' => $sid]);
        $this->assertEquals(count($res['data']['items']), 1);

        $res = DbHelper::selectRefreshToken(['sid' => $sid]);
        $this->assertEquals(count($res['data']['items']), 1);

        $res = PATA::authenticate(['accessToken' => $accessToken]);
        $this->assertEquals($res['result'], true);
    }

    public function testLogoutUser() {
        $res = PATA::logoutUser();
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_AUTH_INVALID_TOKEN);

        $res = PATA::logoutUser(['accessToken' => 'ssss']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_AUTH_TOKEN_NOT_FOUND);

        $res = PATA::loginUser(['email' => 'test3@test.it', 'password' => 'Test123!p3']);
        $this->assertEquals($res['result'], true);
        $sid = $res['data']['sid'];
        $accessToken = $res['data']['accessToken'];

        $res = PATA::logoutUser(['accessToken' => $accessToken]);
        $this->assertEquals($res['result'], true);

        $res = DbHelper::selectAccessToken(['sid' => $sid]);
        $this->assertEquals(count($res['data']['items']), 0);
    }

    public function testForgotPassword() {
        $originalData = self::mockUserData();
        $item = ['email' => 'testForgotPassword@test.it', 'password' => 'Test123!p2'] + $originalData;
        ['data' => ['id' => $userId]] = DbHelper::createUser(['data' => $item]);

        $res = PATA::forgotPassword();
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_FORGOT_PASSWORD_INVALID_EMAIL);

        $res = PATA::forgotPassword(['email' => 'ffffhhhh.it']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_FORGOT_PASSWORD_INVALID_EMAIL);

        $res = PATA::forgotPassword(['email' => 'ffff@hhhh.it']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'], PATA_ERROR_FORGOT_PASSWORD_INVALID_EMAIL);

        $res = PATA::forgotPassword(['email' => 'testForgotPassword@test.it']);
        $this->assertEquals($res['result'], true);
        $this->assertEquals(!!$res['data']['changePasswordToken'], true);
        $this->assertEquals($res['data']['queryResult'] === 1, true);
        $changePasswordToken = $res['data']['changePasswordToken'];

        $res = PATA::forgotPassword(['email' => 'testForgotPassword@test.it']);
        $this->assertEquals($res['result'], false);
        $this->assertEquals($res['error']['code'] === PATA_ERROR_FORGOT_PASSWORD_ALREADY_PRESENT, true);
        $this->assertEquals($res['secondsLeft'] > 0, true);

        $res = DbHelper::updateToken(['token' => $changePasswordToken, 'data' => ['expiration' => 1]]);
        $res = PATA::forgotPassword(['email' => 'testForgotPassword@test.it']);
        $this->assertEquals($res['result'], true);
        $this->assertEquals(!!$res['data']['changePasswordToken'], true);
        $this->assertEquals($res['data']['queryResult'] === 1, true);
        $changePasswordToken2 = $res['data']['changePasswordToken'];

        ['data' => ['items' => $tokens]] = DbHelper::selectToken(['token' => $changePasswordToken]);
        $this->assertEquals(count($tokens) === 0, true);

        ['data' => ['items' => $tokens]] = DbHelper::selectToken(['token' => $changePasswordToken2]);
        $this->assertEquals(count($tokens) === 1, true);

        ['data' => ['items' => $tokens]] = DbHelper::selectChangePasswordToken(['userId' => $userId]);
        $this->assertEquals(count($tokens) === 1, true);
    }
}
