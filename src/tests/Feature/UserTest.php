<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Country;
use App\Consts\ErrorMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private const TESTING_NAME = 'test';
    private const TESTING_USERNAME = 'test';
    private const TESTING_COUNTRY_ID = 1120;

    /**
     * Auth0から取得したID Token
     *
     * @var string|null
     */
    private ?string $id_token = null;

    /**
     * テストユーザのauth_id(sub)
     *
     * @var string|null
     */
    private ?string $testing_auth_id = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->fetchIdToken();
        $this->createTestUser();
    }

    /**
     * Test `/user/profile`
     *
     * @return void
     */
    public function test_getUserProfile(): void
    {
        $response = $this->getJson('/user/profile');
        $response->assertStatus(401)->assertJson(ErrorMessage::MESSAGES['token_does_not_exist']);

        $response = $this->getJson('/user/profile', [
            'Authorization' => 'Bearer '.$this->id_token
        ]);

        $response->assertStatus(200)->assertJson([
            'auth_id' => $this->testing_auth_id,
            'name' => self::TESTING_NAME,
            'username' => self::TESTING_USERNAME,
            'country_id' => self::TESTING_COUNTRY_ID,
            'country_code' => Country::COUNTRY_CODE_LIST[self::TESTING_COUNTRY_ID],
            'country' => Country::COUNTRY_LIST[self::TESTING_COUNTRY_ID],
        ]);
    }

    /**
     * Test `/user/create`
     *
     * @return void
     */
    public function test_createUserProfile(): void
    {
        $this->deleteTestUser();

        // 正常系
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('user/create', [
            'name' => self::TESTING_NAME,
            'username' => self::TESTING_USERNAME,
            'country_id' => self::TESTING_COUNTRY_ID,
        ]);

        $response->assertStatus(200)->assertJson([
            "status" => true
        ]);

        // ユーザーが正常に作成されているか確認
        $this->getJson('/user/profile', [
            'Authorization' => 'Bearer '.$this->id_token
        ])->assertStatus(200)->assertJson([
            'auth_id' => $this->testing_auth_id,
            'name' => self::TESTING_NAME,
            'username' => self::TESTING_USERNAME,
            'country_id' => self::TESTING_COUNTRY_ID,
        ]);

        // 重複した auth_id の登録はできない
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('user/create', [
            'name' => self::TESTING_NAME,
            'username' => self::TESTING_USERNAME.'_2', // usernameの重複によるinvalidを防ぐ
            'country_id' => self::TESTING_COUNTRY_ID,
        ]);

        $response->assertStatus(422)->assertJson(ErrorMessage::MESSAGES['user_already_exist']);


        $this->deleteTestUser();

        // usernameのバリデーションを確認
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('user/create', [
            'name' => self::TESTING_NAME,
            'username' => '1234567890123456',
            'country_id' => self::TESTING_COUNTRY_ID,
        ]);

        $response->assertStatus(200)->assertJson([
            "status" => true
        ]);

        $this->deleteTestUser();

        // 17文字以上は登録できない
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('user/create', [
            'name' => self::TESTING_NAME,
            'username' => '12345678901234567',
            'country_id' => self::TESTING_COUNTRY_ID,
        ]);

        $response->assertStatus(422);

        // 記号は`_` `-`以外は使用できない
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('user/create', [
            'name' => self::TESTING_NAME,
            'username' => self::TESTING_USERNAME.'@',
            'country_id' => self::TESTING_COUNTRY_ID,
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test `/user/update`
     *
     * @return void
     */
    public function test_updateUserProfile() :void
    {
        $additional_text = '_updated';

        // 正常系
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->putJson('user/update', [
            'name' => self::TESTING_NAME.$additional_text,
            'username' => self::TESTING_USERNAME.$additional_text,
            'country_id' => self::TESTING_COUNTRY_ID+10,
        ]);

//        dd($response);

        $response->assertStatus(200)->assertJson([
            "status" => true
        ]);

        // `username`に変更がなくてもリクエストは処理される
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->putJson('user/update', [
            'name' => self::TESTING_NAME,
            'username' => self::TESTING_USERNAME,
            'country_id' => self::TESTING_COUNTRY_ID,
        ]);

        $response->assertStatus(200)->assertJson([
            "status" => true
        ]);

        // 各値が足りない状態でリクエストしても処理される
        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->putJson('user/update', [
            'name' => self::TESTING_NAME,
            'country_id' => self::TESTING_COUNTRY_ID,
        ])->assertStatus(200)->assertJson([
            "status" => true
        ]);

        // すべての値が空でもリクエストは処理される
        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->putJson('user/update')->assertStatus(200)->assertJson([
            "status" => true
        ]);
    }

    /**
     * Test `/user/delete`
     *
     * @return void
     */
    public function test_deleteUserProfile()
    {
        // 正常系
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->deleteJson('user/delete');

        $response->assertStatus(200)->assertJson([
            "status" => true
        ]);

        $this->getJson('/user/profile', [
            'Authorization' => 'Bearer '.$this->id_token
        ])->assertStatus(200)->assertJson([
            'status' => false,
            'message' => 'User profile is not registered',
            'code' => 2000,
        ]);

        // 削除後に再登録できるか
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('user/create', [
            'name' => self::TESTING_NAME,
            'username' => self::TESTING_USERNAME,
            'country_id' => self::TESTING_COUNTRY_ID,
        ]);

        $response->assertStatus(200)->assertJson([
            "status" => true
        ]);

    }

    /**
     * Auth0で作成したテスト用ユーザのID Tokenを取得
     *
     * @return void
     */
    private function fetchIdToken(): void
    {
        $response = Http::asForm()->post('https://'.config('auth0.domain').'/oauth/token', [
            'grant_type' => 'password',
            'client_id'   => config('auth0.clientId'),
            'client_secret' => config('auth0.clientSecret'),
            'username' => config('auth0.testUsername'),
            'password' => config('auth0.testUserPass'),
            'scope'=>'openid'
        ]);

        $token = json_decode($response->body());
        $this->id_token = $token->id_token;

        $decoded_id_token = json_decode(base64_decode(explode('.', $this->id_token)[1]));
        $this->testing_auth_id = $decoded_id_token->sub;
    }

    /**
     * テスト用ユーザを作成
     *
     * @return void
     */
    private function createTestUser(): void
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
    private function deleteTestUser(): void
    {
        User::where('auth_id', $this->testing_auth_id)->delete();
        Cache::forget($this->testing_auth_id);
    }
}
