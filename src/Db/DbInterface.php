<?php
namespace PATA\Db;

/**
 * DB Interface
 * Classes implementing that interface must be compliant with the format of data returned.
 * It's recommended to use AppHelper::returnSuccess() and AppHelper::returnError() functions to return with right format.
 * See LumenDB.php as an example.
 */
interface DbInterface {
    /**
     * selectToken()
     *
     * @param int options['userId'] - filter by user id
     * @param string options['type'] - filter by type
     * @param int options['count'] - if true return items count nuimber instead items array. Default to false.
     * @param string options['token'] - filter by token
     * @param string options['sid'] - filter by sid
     *
     * @return array ["success"=>bool, "data"=>["items"=>[["id"=>".."], ["id"=>".."]]]]
     */
    public function selectToken(array $options): array;

    /**
     * createToken()
     *
     * @param array options['data'] - token data
     * @param string options['data']['created'] - date time in mysql format 'Y-m-d H:i:s'
     * @param string options['data']['modified'] - date time in mysql format 'Y-m-d H:i:s'
     * @param int options['data']['user_id'] - user associated to this token
     * @param string options['data']['sid'] - session id
     * @param string options['data']['token'] - token string
     * @param string options['data']['token_type'] - the type of token (eg. access token, refresh, token, etc..)
     * @param string options['data']['expiration'] - UTC timestamp
     *
     * @return array ["success"=>bool, "data"=>["queryResult"=>bool]] // queryResult = whether the data is inserted or not
     */
    public function createToken(array $options): array;

    /**
     * updateToken()
     *
     * @param array options['data'] - user data to update
     * @param int options['id'] - filtered id to update
     * @param string options['token'] - filtered token to update
     * @param int options['userId'] - filtered userId to update
     * @param string options['sid'] - filtered sid to update
     * @param string options['type'] - filtered type to update
     *
     * @return array ["success"=>bool, "data"=>["queryResult"=>int]] // queryResult = affected rows
     */
    public function updateToken(array $options): array;

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
    public function deleteToken(array $options): array;

    /**
     * selectUser()
     *
     * @param string options['id'] -
     * @param string options['email'] -
     * @param string options['password'] -
     * @param bool options['active'] -
     *
     * @return array ["success"=>bool, "data"=>["items"=> [...]]]
     */
    public function selectUser(array $options): array;

    /**
     * createUser()
     *
     * @param string options['data']['created'] - date time in mysql format 'Y-m-d H:i:s'
     * @param string options['data']['email'] - user email
     * @param string options['data']['password'] - password
     * @param bool options['data']['active'] - accepted values: "0" | false | 0 | "1" | true | 1 - flag indicating the user is active or must confirm the email.
     *
     * @return array ["success"=>bool, "data"=>["items"=> [...]]]
     */
    public function createUser(array $options): array;

    /**
     * updateUser()
     *
     * @param array options['data'] - user data, see createUser() for valid fields
     * @param string options['id'] - id of the user to update
     *
     * @return array ["success"=>bool, "data"=>["items"=> [...]]]
     */
    public function updateUser(array $options): array;
}
