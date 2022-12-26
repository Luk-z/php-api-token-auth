<?php
namespace PATA\Helpers;

class AppHelper
{
    public static function buildErrorData($options = [])
    {
        $message = $options['message'] ?? 'An error happens';
        $code = $options['code'] ?? 'api_error';
        $fields = $options['fields'] ?? '';
        $customData = $options['customData'] ?? [];

        return ['error' => [
            'message' => $message,
            'code' => $code,
            'fields' => $fields,
        ] + $customData];
    }

    public static function returnError($options = [])
    {
        $error = $options['error'] ?? [];
        $customData = $options['customData'] ?? [];
        //use buildErrorData for error data
        return ['result' => false] + self::buildErrorData($error) + $customData;
    }

    //return ["result"=>true, "data"=>$options["data"]];
    public static function returnSuccess($options = [])
    {
        $data = $options['data'] ?? [];
        return ['result' => true, 'data' => $data];
    }
}
