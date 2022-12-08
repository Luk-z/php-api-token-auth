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
}

