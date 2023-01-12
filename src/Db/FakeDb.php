<?php
namespace PATA\Db;

use PATA\PATA;
use PATA\Helpers\AppHelper;

/**
 * Fake db class only for mock/tests purpose
 *  [
 *      "table1"=>[
 *          "1" =>{"id"=>1, ...}
 *      ],
 *      "table2"=>[
 *          "1" =>{"id"=>1, ...}
 *      ],
 *      "table3"=>[
 *          "1" =>{"id"=>1, ...}
 *      ],
 *  ]
 */
class FakeDb implements DbInterface {
    private static $data = [];
    private static $usersTableName = 'users';
    private static $userTokensTableName = 'tokens';

    public function __construct() {
        self::$data[self::$userTokensTableName] = [];
        self::$data[self::$usersTableName] = [];
    }

    public static function delete(array $options = []): void {
        $rows = $options['rows'] ?? [];
        $tableName = $options['tableName'] ?? '';

        foreach ($rows as $k => $row) {
            unset(self::$data[$tableName][$k]);
        }
    }

    public static function update(array $options = []): void {
        $rows = $options['rows'] ?? [];
        $data = $options['data'] ?? [];
        $tableName = $options['tableName'] ?? '';

        foreach ($rows as $k => $row) {
            foreach ($data as $fieldKey => $fieldValue) {
                self::$data[$tableName][$k]->$fieldKey = $fieldValue;
            }
        }
    }

    public static function filterByValue(array $options = []): array {
        $items = $options['items'] ?? [];
        $key = $options['key'] ?? '';
        $value = $options['value'] ?? '';

        if (!$key) {
            return $items;
        }

        return array_filter($items, function ($item) use ($key, $value) {
            if (!isset($item->$key)) {
                return false;
            }
            return $item->$key === $value;
        });
    }

    public function selectToken(array $options = []): array {
        //todo: create abstract method to get metod params (eg. getSelectTokenParams()=>[...])
        $userId = $options['userId'] ?? null;
        $type = $options['type'] ?? null;
        $count = $options['count'] ?? null;
        $token = $options['token'] ?? null;
        $sid = $options['sid'] ?? null;

        $rows = self::$data[self::$userTokensTableName];

        if ($userId !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'user_id', 'value' => $userId
            ]);
        }

        if ($type !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'token_type', 'value' => $type
            ]);
        }

        if ($token !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'token', 'value' => $token
            ]);
        }

        if ($sid !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'sid', 'value' => $sid
            ]);
        }

        if ($count) {
            return AppHelper::returnSuccess(['data' => ['items' => count($rows)]]);
        }

        return AppHelper::returnSuccess(['data' => ['items' => array_values($rows)]]);
    }

    public function createToken(array $options = []): array {
        $data = $options['data'] ?? null;

        if (!$data || (is_array($data) && count($data) === 0)) {
            return AppHelper::returnError(['error' => [
                'message' => 'data not valid',
            ]]);
        }

        if (is_numeric(array_keys($data)[0])) {
            // $data is an array of item to insert
            foreach ($data as $dataItem) {
                $this->createToken(['data' => $dataItem]);
            }
            $queryResult = count($data);
        } else {
            $newId = count(self::$data[self::$userTokensTableName]) + 1;

            if (is_array($data)) {
                $data = (object)$data;
            }

            $data->id = $newId;

            self::$data[self::$userTokensTableName][] = $data;
            $queryResult = 1;
        }

        return AppHelper::returnSuccess(['data' => ['queryResult' => $queryResult]]);
    }

    public function updateToken(array $options = []): array {
        $data = $options['data'] ?? [];
        $id = $options['id'] ?? null;
        $token = $options['token'] ?? null;
        $userId = $options['userId'] ?? null;
        $sid = $options['sid'] ?? null;
        $type = $options['type'] ?? null;

        $rows = self::$data[self::$userTokensTableName];

        if ($id !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'id', 'value' => $id
            ]);
        }

        if ($token !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'token', 'value' => $token
            ]);
        }

        if ($userId !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'user_id', 'value' => $userId
            ]);
        }

        if ($sid !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'sid', 'value' => $sid
            ]);
        }

        if ($type !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'type', 'value' => $type
            ]);
        }

        self::update([
            'rows' => $rows, 'data' => $data, 'tableName' => self::$userTokensTableName
        ]);

        return AppHelper::returnSuccess(['data' => ['queryResult' => count($rows)]]);
    }

    public function deleteToken(array $options = []): array {
        $id = $options['id'] ?? null;
        $token = $options['token'] ?? null;
        $sid = $options['sid'] ?? null;
        $userId = $options['userId'] ?? null;
        $type = $options['type'] ?? null;

        $rows = self::$data[self::$userTokensTableName];

        if ($id !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'id', 'value' => $id
            ]);
        }

        if ($token !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'token', 'value' => $token
            ]);
        }

        if ($sid !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'sid', 'value' => $sid
            ]);
        }

        if ($userId !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'user_id', 'value' => $userId
            ]);
        }

        if ($type !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'token_type', 'value' => $type
            ]);
        }

        self::delete([
            'rows' => $rows, 'tableName' => self::$userTokensTableName
        ]);

        return AppHelper::returnSuccess(['data' => ['queryResult' => count($rows)]]);
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

        $newId = count(self::$data[self::$usersTableName]) + 1;

        self::$data[self::$usersTableName][] = (object)[
            'id' => $newId,
            'created' => $created,
            'email' => $email,
            'password' => $password,
            'active' => $active,
            'modified' => $created,
        ];

        return AppHelper::returnSuccess(['data' => ['id' => $newId]]);
    }

    public function updateUser(array $options = []): array {
        $data = $options['data'] ?? [];
        $id = $options['id'] ?? null;

        $rows = self::$data[self::$usersTableName];

        if ($id !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'id', 'value' => $id
            ]);
        }

        self::update([
            'rows' => $rows, 'data' => $data, 'tableName' => self::$usersTableName
        ]);

        return AppHelper::returnSuccess(['data' => ['queryResult' => count($rows)]]);
    }

    public function selectUser(array $options = []): array {
        $email = $options['email'] ?? null;
        $password = $options['password'] ?? null;
        $active = $options['active'] ?? null;
        $id = $options['id'] ?? null;

        $rows = self::$data[self::$usersTableName];

        if ($email !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'email', 'value' => $email
            ]);
        }

        if ($password !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'password', 'value' => $password
            ]);
        }

        if ($active !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'active', 'value' => $active
            ]);
        }

        if ($id !== null) {
            $rows = self::filterByValue([
                'items' => $rows, 'key' => 'id', 'value' => $id
            ]);
        }

        return AppHelper::returnSuccess(['data' => ['items' => array_values($rows)]]);
    }
}
