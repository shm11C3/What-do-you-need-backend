<?php
namespace App\Consts;

class ErrorMessage
{
    public const MESSAGES = [
        'token_does_not_exist' => ["status" => false, "message" => "Token dose not exist", "code" => 1000],
        'user_does_not_exist'  => ["status" => false, "message" => "User profile is not registered", "code" => 2000],
        'user_already_exist' => ["status" => false, "message" => "User already exist", "code" => 2001],
        'username_is_already_used' => ["status" => false, "message" => "This username is already in use.", "code" => 4000],
    ];

    public const HTTP = [
        400 => '400: Bad Request',
        401 => '401: Unauthorized',
        403 => '403: Forbidden',
        404 => '404: Not Found',
        405 => '405: Method Not Allowed',
        500 => '500: Internal Server Error',
    ];

}
