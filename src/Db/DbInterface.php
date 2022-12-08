<?php

namespace PATA;

/**
 * DB Interface
 * Classes implementing that interface must be compliant with the format of data returned.
 * It's recommended to use AppHelper::returnSuccess() and AppHelper::returnError() functions to return with right format.
 * See LumenDB.php as an example.
 */
interface DB {
    /**
     * selectToken()
     * 
     * @param int options['userId']
     * @param string options['type']
     * @param int options['count']
     * @param string options['token']
     * @param string options['sid']
     * 
     * @return array ["success"=>bool, "data"=>["items"=>[["id"=>".."], ["id"=>".."]]]]
     */
    public function selectToken(array $options) : array;

    /**
     * createToken()
     * 
     * @param array options['data'] - user data
     * @param string options['data']['created'] - date time in mysql format 'Y-m-d H:i:s'
     * @param string options['data']['modified'] - date time in mysql format 'Y-m-d H:i:s'
     * @param int options['data']['user_id'] - 
     * @param string options['data']['sid'] - 
     * @param string options['data']['token'] - 
     * @param string options['data']['token_type'] - 
     * @param string options['data']['expiration'] - UTC timestamp
     * 
     * @return array ["success"=>bool, "data"=>["queryResult"=>bool]] // queryResult = whether the data is inserted or not
     */
    public function createToken(array $options) : array;

    /**
     * updateToken()
     * 
     * @param array options['data'] - user data
     * @param int options['id'] - 
     * @param string options['token'] - 
     * @param int options['userId'] - 
     * @param string options['sid'] - 
     * @param string options['type'] - 
     * 
     * @return array ["success"=>bool, "data"=>["queryResult"=>int]] // queryResult = affected rows
     */
    public function updateToken(array $options) : array;

    /**
     * deleteToken()
     * 
     * @param int options['id'] - database row id to delete
     * @param string options['token'] - token to delete
     * @param string options['sid'] - sid which tokens must be deleted
     * @param int options['userId'] - user id which tokens must be deleted
     * 
     * @return array ["success"=>bool, "data"=>["queryResult"=>int]] // queryResult = affected rows
     */
    public function deleteToken(array $options) : array;

    /**
     * selectUser()
     * 
     * @param string options['email'] -
     * @param string options['password'] - 
     * @param bool options['active'] - 
     * 
     * @return array ["success"=>bool, "data"=>["items"=> [...]]]
     */
    public function selectUser(array $options) : array;

    /**
     * createUser()
     * 
     * @param string options['created'] -
     * @param string options['email'] -
     * @param string options['password'] - 
     * @param bool options['active'] - 
     * 
     * @return array ["success"=>bool, "data"=>["items"=> [...]]]
     */
    public function createUser(array $options) : array;

    /**
     * updateUser()
     * 
     * @param array options['data'] -user data, see createUser() for valid fields
     * @param string options['id'] - id of the user to update
     * 
     * @return array ["success"=>bool, "data"=>["items"=> [...]]]
     */
    public function updateUser(array $options) : array;
}