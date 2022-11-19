<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfigMfaRequest;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Auth;
use Illuminate\Support\Facades\Http;
use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    /**
     * Auth0で設定したMFAのプロバイダー
     *
     * @var string 'duo|google-authenticator'
     */
    const MFA_PROVIDER = 'google-authenticator';

    /**
     * @param Auth $auth
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
        $configuration = new SdkConfiguration([
            'domain' => config('auth0.domain'),
            'clientId' => config('auth0.managementId'),
            'clientSecret' => config('auth0.managementSecret'),
            'cookieSecret' => config('auth0.cookieSecret'),
          ]);

        $this->auth0 = new Auth0($configuration);
    }

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
        $response = $this->auth0->management()->jobs()->createSendVerificationEmail($request->subject);

        if ($response->getStatusCode() >= 400){
          abort(500);
        }

        return response()->json(['status' => true]);
    }

    /**
     * MFAの有効・無効を設定
     *
     * @param ConfigMfaRequest $request
     * @return Response ['result' => $use_mfa]
     */
    public function configMfa(ConfigMfaRequest $request)
    {
        $auth_id = $request->subject;
        $use_mfa = $request['mfa'];

        $this->auth->updateAuth0Account($auth_id, ['user_metadata' =>
            ['use_mfa' => $use_mfa]
        ]);

        // MFAを無効にする場合MFAプロバイダを削除する
        if (!$use_mfa) {
            $response = $this->auth0->management()->users()->deleteMultifactorProvider($auth_id, self::MFA_PROVIDER);

            if ($response->getStatusCode() >= 400){
                abort(500);
            }
        }

        return response()->json([
            'status' => true,
            'result' => $use_mfa
        ]);
    }
}
