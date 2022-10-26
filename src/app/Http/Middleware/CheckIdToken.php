<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use App\Consts\ErrorMessage;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Auth0\SDK\Auth0;
use Auth0\SDK\Token;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class CheckIdToken
{
    /**
     * ユーザデータをキャッシュする時間
     *
     * @var integer
     */
    private int $cache_minutes = 30;

    /**
     * IDトークンから取得した`sub`(`auth_id`)
     *
     * @var string|null
     */
    private ?string $auth_id = null;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        $request_status = $request->getClientIp().' '.$request->method().': '.$request->fullUrl();

        // 不正なパラメータをバリデーション
        if($request->subject || $request->user || $request->email){
            Log::error('[Request Error] '.$request_status.' Invalid parameter.');
            return response()->json(["message" => '422 : '.HttpResponse::$statusTexts[422]], 422);
        }

        // リクエストヘッダにBearerトークンが存在するか確認
        if (empty($request->bearerToken())) {
            // IDトークンが存在しなくても権限が`any`の場合はコントローラに渡す
            if($role == 'any'){
                return $next($request);
            }

            Log::error('[Token Error] '.$request_status.' Token does not exist.');
            return response()->json(ErrorMessage::MESSAGES['token_does_not_exist'], 401);
        }

        $id_token = $request->bearerToken();

        $auth0 = new Auth0([
            'domain'       => config('auth0.domain'),
            'clientId'     => config('auth0.clientId'),
            'clientSecret' => config('auth0.clientSecret'),
            'tokenJwksUri' => 'https://'.config('auth0.domain').'/.well-known/jwks.json',
            'tokenCache' => null,
            'tokenCacheTtl' => 43200,
            'cookieSecret' => config('auth0.cookieSecret'),
        ]);

        // SDKの設定でキャッシュを有効化させる
        $tokenCache = new FilesystemAdapter();
        $auth0->configuration()->setTokenCache($tokenCache);

        // IDトークンの検証・デコード
        try {
            $auth0->decode($id_token, null, null, null, null, null, null, Token::TYPE_ID_TOKEN);
        } catch (\Exception $e) {
            Log::error('[Token Error] '.$request_status.' '.$e->getMessage());

            return response()->json([
                "message" => config('app.debug') ? $e->getMessage() : '401 : '.HttpResponse::$statusTexts[401],
                "code" => 1001
            ], 401);
        }

        //IDトークンに格納されたClaimを取得
        $token = new Token($auth0->configuration(), $id_token, Token::TYPE_ID_TOKEN);
        $payload = json_decode($token->toJson());

        $this->auth_id = $payload->sub;

        $user = json_decode($this->getAuthUser());

        // user_idを$requestに追加する。
        $request->merge([
            'subject' => $this->auth_id,
            'email' => $payload->email,
            'user' => $user,
        ]);

        return $next($request);
    }

    /**
     * キャッシュから`auth_id`に一致するユーザを返す。存在しな場合はDBを参照する
     *
     * @return ?string
     */
    private function getAuthUser()
    {
        return Cache::remember($this->auth_id, $this->cache_minutes, function () {
            $u = DB::table('users')->where('auth_id', $this->auth_id)->where('delete_flg', 0)->get(['auth_id', 'name', 'username', 'country_id', 'created_at']);
            return json_encode($u);
        });
    }
}
