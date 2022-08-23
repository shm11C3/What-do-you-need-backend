<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    /**
     * パスワード変更リクエストを送信
     *
     * @param Request $request
     * @return void
     */
    public function requestResetPasswordMail(Request $request)
    {
        $email = $request->email;

        $response = Http::asForm()->post('https://'.config('auth0.domain').'/dbconnections/change_password', [
            'client_id' => config('auth0.clientId'),
            'email' => $email,
            'connection' => 'Username-Password-Authentication',
        ]);

        // 例外が発生した場合はHTTPエラーを返す
        if ($response->failed()) {
            abort($response->status());
        }

        return response()->json(['status' => true]);
    }
}
