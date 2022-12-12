<?php

namespace PATA\Helpers;

class ValidateHelper{
    //@todo do some smarter cheks
    static function refreshToken($options=[]){
      $value = $options["value"] ?? "";

      return !!$value;
    }

    //@todo do some smarter cheks
    static function accessToken($options=[]){
      $value = $options["value"] ?? "";

      return !!$value;
    }

    //@todo do some smarter cheks
    static function sidToken($options=[]){
      $value = $options["value"] ?? "";

      return !!$value;
    }

    static function email($options=[]){
      $value = $options["value"] ?? "";

      return !!filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    static function password($options=[]){
      $value = $options["value"] ?? "";

      return !!preg_match(PATA_REGEX_PASSWORD, $value);
    }
}

