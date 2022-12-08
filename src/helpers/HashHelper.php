<?php

namespace PATA\Helpers;

class HashHelper {

  private static $hash = null;

  public static function init($options = []){
    $handler = $options["handler"] ?? false;

    if($handler){
	    self::$hash = $handler;
    }
  }

  // to use outside this class
  public static function getInstance(){
    if(!self::$hash){
        //die bruttaly
        die("PATA/HashHelper::getInstance hash not setted. HashHelper::init(['handler'=>your_hash_handle]) must be called.");
    }
	return self::$hash;
  }

  static function hash($options=[]){
    return self::$hash->hash($options);
  }

  static function hashCheck($options=[]){
    return self::$hash->hashCheck($options);
  }
}