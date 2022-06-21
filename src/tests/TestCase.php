<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{

    use CreatesApplication;

    /**
     * Testing user
     */
    protected const TESTING_NAME = 'test';
    protected const TESTING_USERNAME = 'test';
    protected const TESTING_COUNTRY_ID = 1120;

    /**
     * Testing post
     */
    public const TESTING_POST_ULID = '01G5YHBC8X16BNZSX81AXM97PM';
    public const TESTING_POST_TITLE = 'test post';
    public const TESTING_POST_CONTENT = "This post is created for automated testing. \nCording tests is tedious.";

    /**
     * Testing category
     */
    public const TESTING_CATEGORY_UUID = '2dd30f7c-8cc9-d52c-048d-456031525669';
    public const TESTING_CATEGORY_NAME = 'test category';

    /**
     * Auth0から取得したID Token
     *
     * @var string|null
     */
    protected ?string $id_token = null;

    /**
     * テストユーザのauth_id(sub)
     *
     * @var string|null
     */
    protected ?string $testing_auth_id = null;

    /**
     * Auth0で作成したテスト用ユーザのID Tokenを取得
     *
     * @return string
     */
    private function fetchIdToken(): string
    {
        $token = Cache::remember('id_token'.config('auth0.testUsername'), 1440, function(){
            $res = Http::asForm()->post('https://'.config('auth0.domain').'/oauth/token', [
                'grant_type' => 'password',
                'client_id'   => config('auth0.clientId'),
                'client_secret' => config('auth0.clientSecret'),
                'username' => config('auth0.testUsername'),
                'password' => config('auth0.testUserPass'),
                'scope'=>'openid'
             ]);

            return $res->body();
        });

        $token = json_decode($token);
        return $token->id_token;
    }

    /**
     * IDトークンをデコードして変数に代入する
     *
     * @return void
     */
    protected function storeIdToken(): void
    {
        $id_token = $this->fetchIdToken();
        $decoded_id_token = json_decode(base64_decode(explode('.', $id_token)[1]));

        // トークンの有効期限が切れている場合キャッシュを削除し取得しなおす
        if($decoded_id_token->exp < strtotime('now')){
            Cache::forget('id_token'.config('auth0.testUsername'));
            $id_token = $this->fetchIdToken();
            $decoded_id_token = json_decode(base64_decode(explode('.', $id_token)[1]));
        }

        $this->id_token = $id_token;
        $this->testing_auth_id = $decoded_id_token->sub;
    }

    /**
     * テスト用ユーザを作成
     *
     * @return void
     */
    protected function createTestUser(): void
    {
        User::create([
            'auth_id' => $this->testing_auth_id,
            'name' => self::TESTING_NAME,
            'username' => self::TESTING_USERNAME,
            'country_id' => self::TESTING_COUNTRY_ID,
        ]);
        Cache::forget($this->testing_auth_id);
    }

        /**
     * テスト用ユーザを削除
     *
     * @return void
     */
    protected function deleteTestUser(): void
    {
        User::where('auth_id', $this->testing_auth_id)->delete();
        Cache::forget($this->testing_auth_id);
    }

}
