<?php

namespace PATA\Helpers;
use PATA\PATA;

class DbHelper {

  private static $db = null;

  public static function init($options = []){
    $handler = $options["handler"] ?? false;

    if($handler){
	    self::$db = $handler;
    }
  }

  // to use outside this class
  public static function getInstance(){
    if(!self::$db){
        //die bruttaly
        die("PATA/DbHelper::getInstance db not setted. DbHelper::init(['handler'=>your_db_handle]) must be called.");
    }
	  return self::$db;
  }

  /**
   * TOKEN
   */

  static function selectActivateUserToken($options=[]){
      //@todo maybe cache value
      return self::selectToken($options + ["type" => PATA::$activateTokenName]);
  }

  public static function selectAccessToken($options=[]){
    //@todo maybe cache value
    return self::selectToken($options + ["type" => PATA::$accessTokenName]);
  }

  static function selectRefreshToken($options=[]){
    //@todo maybe cache value
    return self::selectToken($options + ["type" => PATA::$refreshTokenName]);
  }

  static function selectToken($options=[]){
    return self::$db->selectToken($options);
  }

  static function createToken($options=[]){
    return self::$db->createToken($options);
  }

  static function updateToken($options=[]){
    return self::$db->updateToken($options);
  }

  static function deleteToken($options=[]){
    return self::$db->deleteToken($options);
  }

  /**
   * USER
   */

  //create user
  static function createUser($options=[]){
    return self::$db->createUser($options);
  }

  static function updateUser($options=[]){
    return self::$db->updateUser($options);
  }

  static function selectUser($options=[]){
    return self::$db->selectUser($options);
  }
}