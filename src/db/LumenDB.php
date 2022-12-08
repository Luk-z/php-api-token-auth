<?php

namespace PATA\Db;

class LumenDB implements DB{
    public function selectToken(array $options = []) : array {
        $userId = $options["userId"] ?? null;
        $type = $options["type"] ?? null;
        $count = $options["count"] ?? null;
        $token = $options["token"] ?? null;
        $sid = $options["sid"] ?? null;
    
        $query = app('db')->table(PATA::$userTokensTableName)->select("*");
    
        if($userId !== null){
            $query->where("user_id", "=", $userId);
        }
    
        if($type !== null){
            $query->where("token_type", "=", $type);
        }
    
        if($token !== null){
            $query->where("token", "=", $token);
        }
    
        if($sid !== null){
            $query->where("sid", "=", $sid);
        }
    
        if($count){
            $items = $query->count();
        }
        else{
            $items = $query->get();
        }
    
        return AppHelper::returnSuccess(["data"=>["items"=>$items]]);
    }

    public function createToken(array $options = []) : array {
        $data = $options["data"] ?? [];

        $queryResult = app('db')->table(PATA::$userTokensTableName)->insert($data);

        return AppHelper::returnSuccess(["data"=>["queryResult"=>$queryResult]]);
    }

    public function updateToken(array $options = []) : array {
        $data = $options["data"] ?? [];
        $id = $options["id"] ?? null;
        $token = $options["token"] ?? null;
        $userId = $options["userId"] ?? null;
        $sid = $options["sid"] ?? null;
        $type = $options["type"] ?? null;

        $query = app('db')->table(PATA::$userTokensTableName);

        if($id !== null){
            $query->where("id", "=", $id);
        }

        if($token !== null){
            $query->where("token", "=", $token);
        }

        if($userId !== null){
            $query->where("user_id", "=", $userId);
        }

        if($sid !== null){
            $query->where("sid", "=", $sid);
        }

        if($type !== null){
            $query->where("type", "=", $type);
        }

        $affected = $query->update($data);

        return AppHelper::returnSuccess(["data"=>["queryResult"=>$affected]]);
    }

    public function deleteToken(array $options = []) : array {
        $id = $options["id"] ?? null;
        $token = $options["token"] ?? null;
        $sid = $options["sid"] ?? null;
        $userId = $options["userId"] ?? null;

        $query = app('db')->table(PATA::$userTokensTableName);

        if($id !== null){
            $query->where("id", "=", $id);
        }

        if($token !== null){
            $query->where("token", "=", $token);
        }

        if($sid !== null){
            $query->where("sid", "=", $sid);
        }

        if($userId !== null){
            $query->where("user_id", "=", $userId);
        }

        $affected = $query->delete();

        return AppHelper::returnSuccess(["data" => ["queryResult" => $affected]]);
    }

    public function createUser(array $options = []) : array {
        $created = $options["created"] ?? null;
        $email = $options["email"] ?? null;
        $password = $options["password"] ?? null;
        $active = $options["active"] ?? null;

        if($created===null || $email===null || $password===null || $active===null){
            return AppHelper::returnError(["error"=>[
                "message" => "data not valid",
            ]]);
        }

        // app('db')->insert( 
        //     "INSERT INTO ".PATA::$usersTableName." (created, email, password, active) VALUES ( ?, ?, ?, ?)", 
        //     [$created, $email, $password, $active] 
        // );

        $id = app('db')->table(PATA::$usersTableName)->insertGetId([
            "created" => $created,
            "email" => $email,
            "password" => $password,
            "active" => $active,
            "modified" => $created,
        ]);

        return AppHelper::returnSuccess(["data" => ["id" => $id]]);
    }

    public function updateUser(array $options = []) : array {
        $data = $options["data"] ?? [];
        $id = $options["id"] ?? null;

        $query = app('db')->table(PATA::$usersTableName);

        if($id !== null){
            $query->where("id", "=", $id);
        }

        $affected = $query->update($data);

        return AppHelper::returnSuccess(["data" => ["queryResult" => $affected]]);
    }
    
    public function selectUser(array $options = []) : array {
        $email = $options["email"] ?? null;
        $password = $options["password"] ?? null;
        $active = $options["active"] ?? null;

        $query = app('db')->table(PATA::$usersTableName)->select("*");

        if($email !== null){
            $query->where("email", "=", $email);
        }

        if($password !== null){
            $query->where("password", "=", $password);
        }

        if($active !== null){
            $query->where("active", "=", $active);
        }

        $items = $query->get();

        return AppHelper::returnSuccess(["data" => ["items" => $items]]);
    }
}