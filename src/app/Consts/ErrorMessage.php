<?php
namespace App\Consts;

class ErrorMessage
{
    public const ERROR_MESSAGE_LIST = [

        'token_does_not_exist' => ["message" => "Token dose not exist", "code" => 1000],
        'user_does_not_exist'  => ["message" => "User profile is not registered", "code" => 2000],
        'user_already_exist' => ["message" => "User already exist", "code" => 2001],
    ];

}
