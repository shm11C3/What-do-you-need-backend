<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;

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

    /**
     * Request Auth0 Management API to send authentication email
     *
     * @param Request $request
     * @return response
     */
    public function resendVerificationEmail(Request $request)
    {
        $configuration = new SdkConfiguration([
            'domain' => config('auth0.domain'),
            'clientId' => config('auth0.managementId'),
            'clientSecret' => config('auth0.managementSecret'),
            'cookieSecret' => config('auth0.cookieSecret'),
          ]);

        $auth0 = new Auth0($configuration);

        $response = $auth0->management()->jobs()->createSendVerificationEmail($request->subject);

        if ($response->getStatusCode() >= 400){
          abort(500);
        }

        return response()->json(['status' => true]);
    }
}
