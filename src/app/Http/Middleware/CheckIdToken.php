<?php

namespace App\Http\Middleware;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Auth0\SDK\Auth0;
use Auth0\SDK\Token;
use Closure;

class CheckIdToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $configure = [
            'domain'       => config('auth0.domain'),
            'clientId'     => config('auth0.clientId'),
            'clientSecret' => config('auth0.clientSecret'),
            'tokenJwksUri' => 'https://'.config('auth0.domain').'/.well-known/jwks.json',
            'tokenCache' => null,
            'tokenCacheTtl' => 43200
        ];

        $auth0 = new Auth0($configure);

        // SDKの設定でキャッシュを有効化させる
        $tokenCache = new FilesystemAdapter();
        $auth0->configuration()->setTokenCache($tokenCache);

        // リクエストヘッダにBearerトークンが存在するか確認
        if (empty($request->bearerToken())) {
            return response()->json(["message" => "Token dose not exist"], 401);
        }

        $id_token = $request->bearerToken();

        // IDトークンの検証・デコード
        try {
            $auth0->decode($id_token, null, null, null, null, null, null, \Auth0\SDK\Token::TYPE_ID_TOKEN);
        } catch (\Exception $e) {
            return config('app.debug') ?
            response()->json(["message" => $e->getMessage()], 401) :
            response()->json(["message" => "401: Unauthorized"], 401);
        }

        $token = new Token($auth0->configuration(), $id_token, \Auth0\SDK\Token::TYPE_ID_TOKEN);
        $payload = json_decode($token->toJson()); //IDトークンに格納されたClaimを取得

        // user_idを$requestに追加する。
        $request->merge([
            'auth0_user_id' => $payload->sub
        ]);

        return $next($request);

    }
}
