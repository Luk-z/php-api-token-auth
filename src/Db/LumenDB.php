<?php
namespace PATA\Db;

use PATA\PATA;
use PATA\Helpers\AppHelper;

class LumenDB implements DbInterface {
    public static $usersTableName;
    public static $userTokensTableName;

    public function __construct($options = []) {
        self::$usersTableName = $options['usersTableName'] ?? PATA_DEFAULT_USERS_TABLE_NAME;
        self::$userTokensTableName = $options['userTokensTableName'] ?? PATA_DEFAULT_TOKENS_TABLE_NAME;
    }

    public function selectToken(array $options = []): array {
        $userId = $options['userId'] ?? null;
        $type = $options['type'] ?? null;
        $count = $options['count'] ?? null;
        $token = $options['token'] ?? null;
        $sid = $options['sid'] ?? null;

        $query = app('db')->table(self::$userTokensTableName)->select('*');

        if ($userId !== null) {
            $query->where('user_id', '=', $userId);
        }

        if ($type !== null) {
            $query->where('token_type', '=', $type);
        }

        if ($token !== null) {
            $query->where('token', '=', $token);
        }

        if ($sid !== null) {
            $query->where('sid', '=', $sid);
        }

        if ($count) {
            $items = $query->count();
        } else {
            $items = $query->get();
        }

        return AppHelper::returnSuccess(['data' => ['items' => $items]]);
    }

    public function createToken(array $options = []): array {
        $data = $options['data'] ?? [];

        $queryResult = app('db')->table(self::$userTokensTableName)->insert($data);

        return AppHelper::returnSuccess(['data' => ['queryResult' => $queryResult]]);
    }

    public function updateToken(array $options = []): array {
        $data = $options['data'] ?? [];
        $id = $options['id'] ?? null;
        $token = $options['token'] ?? null;
        $userId = $options['userId'] ?? null;
        $sid = $options['sid'] ?? null;
        $type = $options['type'] ?? null;

        $query = app('db')->table(self::$userTokensTableName);

        if ($id !== null) {
            $query->where('id', '=', $id);
        }

        if ($token !== null) {
            $query->where('token', '=', $token);
        }

        if ($userId !== null) {
            $query->where('user_id', '=', $userId);
        }

        if ($sid !== null) {
            $query->where('sid', '=', $sid);
        }

        if ($type !== null) {
            $query->where('token_type', '=', $type);
        }

        $affected = $query->update($data);

        return AppHelper::returnSuccess(['data' => ['queryResult' => $affected]]);
    }

    public function deleteToken(array $options = []): array {
        $id = $options['id'] ?? null;
        $token = $options['token'] ?? null;
        $sid = $options['sid'] ?? null;
        $userId = $options['userId'] ?? null;

        $query = app('db')->table(self::$userTokensTableName);

        if ($id !== null) {
            $query->where('id', '=', $id);
        }

        if ($token !== null) {
            $query->where('token', '=', $token);
        }

        if ($sid !== null) {
            $query->where('sid', '=', $sid);
        }

        if ($userId !== null) {
            $query->where('user_id', '=', $userId);
        }

        $affected = $query->delete();

        return AppHelper::returnSuccess(['data' => ['queryResult' => $affected]]);
    }

    public function createUser(array $options = []): array {
        $data = $options['data'] ?? [];
        $created = $data['created'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $active = $data['active'] ?? null;

        if ($created === null || $email === null || $password === null || $active === null) {
            return AppHelper::returnError(['error' => [
                'message' => 'data not valid',
            ]]);
        }

        // app('db')->insert(
        //     "INSERT INTO ".self::$usersTableName." (created, email, password, active) VALUES ( ?, ?, ?, ?)",
        //     [$created, $email, $password, $active]
        // );

        $id = app('db')->table(self::$usersTableName)->insertGetId([
            'created' => $created,
            'email' => $email,
            'password' => $password,
            'active' => $active,
            'modified' => $created,
        ]);

        return AppHelper::returnSuccess(['data' => ['id' => $id]]);
    }

    public function updateUser(array $options = []): array {
        $data = $options['data'] ?? [];
        $id = $options['id'] ?? null;

        $query = app('db')->table(self::$usersTableName);

        if ($id !== null) {
            $query->where('id', '=', $id);
        }

        $affected = $query->update($data);

        return AppHelper::returnSuccess(['data' => ['queryResult' => $affected]]);
    }

    public function selectUser(array $options = []): array {
        $email = $options['email'] ?? null;
        $password = $options['password'] ?? null;
        $active = $options['active'] ?? null;
        $id = $options['id'] ?? null;

        $query = app('db')->table(self::$usersTableName)->select('*');

        if ($email !== null) {
            $query->where('email', '=', $email);
        }

        if ($password !== null) {
            $query->where('password', '=', $password);
        }

        if ($active !== null) {
            $query->where('active', '=', $active);
        }

        if ($id !== null) {
            $query->where('ID', '=', intval($id));
        }

        $items = $query->get();

        return AppHelper::returnSuccess(['data' => ['items' => $items]]);
    }
}
