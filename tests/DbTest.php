<?php

use PHPUnit\Framework\TestCase;
use PATA\PATA;
use PATA\Db\FakeDb;
use PATA\Security\FakeHash;
use PATA\Helpers\DbHelper;

final class DbTest extends TestCase {
    public static function mockTokenData() {
        return [
            'created' => '2022-01-01 12:00:00',
            'modified' => '2022-01-01 12:00:00',
            'user_id' => 1,
            'sid' => 1234,
            'token' => 12345,
            'token_type' => PATA::$accessTokenName,
            'expiration' => 9999999999999,
        ];
    }

    public static function mockUserData() {
        return [
            'id' => 1,
            'created' => '2022-01-01 12:00:00',
            'email' => 'test@test.it',
            'password' => 'p1',
            'active' => 0,
        ];
    }

    public static function initDb() {
        //init two times else PATA::$usersTableName is not defined when FakeDb is istantiated
        PATA::init();
        PATA::init([
            'dbHandler' => new FakeDb(),
            'hashHandler' => new FakeHash(),
        ]);
    }

    /*
    START TOKEN TESTS
    */
    public function testCreateToken() {
        self::initDb();

        $originalData = self::mockTokenData();

        $res = DbHelper::createToken();
        $this->assertEquals($res['result'], false);

        $item = $originalData;
        $res = DbHelper::createToken(['data' => $item]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['queryResult'], 1);

        $item = ['token' => 22222, 'sid' => 3333] + $originalData;
        $res = DbHelper::createToken(['data' => $item]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['queryResult'], 1);

        $item = ['token' => 44444, 'sid' => 5555] + $originalData;
        $res = DbHelper::createToken(['data' => $item]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['queryResult'], 1);
    }

    public function testUpdateToken() {
        $item = ['sid' => 55551];
        $res = DbHelper::updateToken(['id' => 3, 'data' => $item]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['queryResult'], 1);

        $res = DbHelper::getInstance()->selectToken(['sid' => 55551]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals(count($res['data']['items']), 1);
        $this->assertEquals($res['data']['items'][0]->sid, 55551);
    }

    public function testSelectToken() {
        // self::initDb();

        $res = DbHelper::getInstance()->selectToken();
        $this->assertEquals($res['result'], true);
        $this->assertEquals(count($res['data']['items']), 3);
        $this->assertEquals($res['data']['items'][0]->id, 1);
        $this->assertEquals($res['data']['items'][0]->user_id, 1);
        $this->assertEquals($res['data']['items'][0]->sid, 1234);
        $this->assertEquals($res['data']['items'][0]->token, 12345);
        $this->assertEquals($res['data']['items'][0]->token_type, PATA::$accessTokenName);
        $this->assertEquals($res['data']['items'][1]->id, 2);
        $this->assertEquals($res['data']['items'][1]->token, 22222);

        $res = DbHelper::getInstance()->selectToken(['userId' => 999]);
        $this->assertEquals(count($res['data']['items']), 0);

        $res = DbHelper::getInstance()->selectToken(['userId' => 1, 'token' => 22222]);
        $this->assertEquals(count($res['data']['items']), 1);
        $this->assertEquals($res['data']['items'][0]->user_id, 1);
        $this->assertEquals($res['data']['items'][0]->token, 22222);
        $this->assertEquals($res['data']['items'][0]->sid, 3333);
    }

    public function testDeleteToken() {
        $originalData = self::mockTokenData();

        $item = ['token' => 'toDelete', 'sid' => 2] + $originalData;
        $res = DbHelper::createToken(['data' => $item]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['queryResult'], 1);

        $res = DbHelper::deleteToken(['token' => 'toDelete']);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['queryResult'], 1);

        $res = DbHelper::getInstance()->selectToken(['token' => 'toDelete']);
        $this->assertEquals(count($res['data']['items']), 0);
    }

    public function testSelectAccessToken() {
        $originalData = self::mockTokenData();

        $res = DbHelper::selectAccessToken();
        $this->assertEquals($res['result'], true);
        $this->assertEquals(count($res['data']['items']), 3);

        $res = DbHelper::selectAccessToken([
            'sid' => 3333,
            'token' => 22222,
        ]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals(count($res['data']['items']), 1);
        $this->assertEquals($res['data']['items'][0]->sid, 3333);
        $this->assertEquals($res['data']['items'][0]->token, 22222);
    }

    public function testSelectActivateUserToken() {
        $originalData = self::mockTokenData();

        $res = DbHelper::selectActivateUserToken();
        $this->assertEquals($res['result'], true);
        $this->assertEquals(count($res['data']['items']), 0);

        $item = [
            'token_type' => PATA::$activateTokenName,
            'sid' => 'testSelectActivateUserTokenSid1',
            'token' => 'testSelectActivateUserTokenToken1'
        ] + $originalData;
        $res = DbHelper::createToken(['data' => $item]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['queryResult'], 1);

        $item = [
            'token_type' => PATA::$activateTokenName,
            'sid' => 'testSelectActivateUserTokenSid2',
            'token' => 'testSelectActivateUserTokenToken2'
        ] + $originalData;
        $res = DbHelper::createToken(['data' => $item]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['queryResult'], 1);

        $res = DbHelper::selectActivateUserToken();
        $this->assertEquals($res['result'], true);
        $this->assertEquals(count($res['data']['items']), 2);
        $this->assertEquals($res['data']['items'][0]->token, 'testSelectActivateUserTokenToken1');
        $this->assertEquals($res['data']['items'][1]->token, 'testSelectActivateUserTokenToken2');

        $res = DbHelper::selectActivateUserToken([
            'sid' => 'testSelectActivateUserTokenSid2',
            'token' => 'testSelectActivateUserTokenToken2'
        ]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals(count($res['data']['items']), 1);
        $this->assertEquals($res['data']['items'][0]->sid, 'testSelectActivateUserTokenSid2');
        $this->assertEquals($res['data']['items'][0]->token, 'testSelectActivateUserTokenToken2');
    }

    public function testSelectRefreshToken() {
        $originalData = self::mockTokenData();

        $res = DbHelper::selectRefreshToken();
        $this->assertEquals($res['result'], true);
        $this->assertEquals(count($res['data']['items']), 0);

        $item = [
            'token_type' => PATA::$refreshTokenName,
            'sid' => 'testSelectRefreshTokenSid1',
            'token' => 'testSelectRefreshTokenToken1'
        ] + $originalData;
        $res = DbHelper::createToken(['data' => $item]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['queryResult'], 1);

        $item = [
            'token_type' => PATA::$refreshTokenName,
            'sid' => 'testSelectRefreshTokenSid2',
            'token' => 'testSelectRefreshTokenToken2'
        ] + $originalData;
        $res = DbHelper::createToken(['data' => $item]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['queryResult'], 1);

        $res = DbHelper::selectRefreshToken();
        $this->assertEquals($res['result'], true);
        $this->assertEquals(count($res['data']['items']), 2);
        $this->assertEquals($res['data']['items'][0]->token, 'testSelectRefreshTokenToken1');
        $this->assertEquals($res['data']['items'][1]->token, 'testSelectRefreshTokenToken2');

        $res = DbHelper::selectRefreshToken([
            'sid' => 'testSelectRefreshTokenSid2',
            'token' => 'testSelectRefreshTokenToken2'
        ]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals(count($res['data']['items']), 1);
        $this->assertEquals($res['data']['items'][0]->sid, 'testSelectRefreshTokenSid2');
        $this->assertEquals($res['data']['items'][0]->token, 'testSelectRefreshTokenToken2');
    }

    /*
     START USER TESTS
    */
    public function testCreateUser() {
        self::initDb();

        $originalData = self::mockUserData();

        $res = DbHelper::createUser(['data' => []]);
        $this->assertEquals($res['result'], false);

        $item = $originalData;
        $res = DbHelper::createUser(['data' => $item]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['id'], 1);

        $item = ['id' => 2, 'email' => 'test2@test.it', 'password' => 'p2'] + $originalData;
        $res = DbHelper::createUser(['data' => $item]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['id'], 2);

        $item = ['id' => 3, 'email' => 'test3@test.it', 'password' => 'p3'] + $originalData;
        $res = DbHelper::createUser(['data' => $item]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['id'], 3);
    }

    public function testUpdateUser() {
        $item = ['active' => 1];
        $res = DbHelper::updateUser(['id' => 3, 'data' => $item]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals($res['data']['queryResult'], 1);

        $res = DbHelper::selectUser(['active' => 1]);
        $this->assertEquals($res['result'], true);
        $this->assertEquals(count($res['data']['items']), 1);
        $this->assertEquals($res['data']['items'][0]->active, 1);
        $this->assertEquals($res['data']['items'][0]->id, 3);
    }

    public function testSelectUser() {
        // self::initDb();

        $res = DbHelper::selectUser();
        $this->assertEquals($res['result'], true);
        $this->assertEquals(count($res['data']['items']), 3);
        $this->assertEquals($res['data']['items'][0]->id, 1);
        $this->assertEquals($res['data']['items'][0]->email, 'test@test.it');
        $this->assertEquals($res['data']['items'][1]->id, 2);
        $this->assertEquals($res['data']['items'][1]->email, 'test2@test.it');
        $this->assertEquals($res['data']['items'][2]->id, 3);
        $this->assertEquals($res['data']['items'][2]->email, 'test3@test.it');

        $res = DbHelper::selectUser(['email' => 999]);
        $this->assertEquals(count($res['data']['items']), 0);

        $res = DbHelper::selectUser(['email' => 'test2@test.it', 'password' => 'p2']);
        $this->assertEquals(count($res['data']['items']), 1);
        $this->assertEquals($res['data']['items'][0]->id, 2);
        $this->assertEquals($res['data']['items'][0]->email, 'test2@test.it');
        $this->assertEquals($res['data']['items'][0]->password, 'p2');
    }
}
