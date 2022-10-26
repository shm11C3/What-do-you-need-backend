<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Country;
use App\Consts\ErrorMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->storeIdToken();
        $this->createTestUser();
    }

    /**
     * Test `/my-profile`
     *
     * @return void
     */
    public function test_getUserProfile(): void
    {
        $response = $this->getJson('/my-profile');
        $response->assertStatus(401)->assertJson(ErrorMessage::MESSAGES['token_does_not_exist']);

        $response = $this->getJson('/my-profile', [
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
     * Test `POST:/user`
     *
     * @return void
     */
    public function test_createUserProfile(): void
    {
        $this->deleteTestUser();

        // 正常系
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('user', [
            'name' => self::TESTING_NAME,
            'username' => self::TESTING_USERNAME,
            'country_id' => self::TESTING_COUNTRY_ID,
        ]);

        $response->assertStatus(200)->assertJson([
            "status" => true
        ]);

        // ユーザーが正常に作成されているか確認
        $this->getJson('/my-profile', [
            'Authorization' => 'Bearer '.$this->id_token
        ])->assertStatus(200)->assertJson([
            'auth_id' => $this->testing_auth_id,
            'name' => self::TESTING_NAME,
            'username' => self::TESTING_USERNAME,
            'country_id' => self::TESTING_COUNTRY_ID,
        ])->assertJsonMissingExact(['created_at' => null])
        ->assertJsonMissingExact(['updated_at' => null]);

        // 重複した auth_id の登録はできない
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('user', [
            'name' => self::TESTING_NAME,
            'username' => self::TESTING_USERNAME.'_2', // usernameの重複によるinvalidを防ぐ
            'country_id' => self::TESTING_COUNTRY_ID,
        ]);

        $this->deleteTestUser();

        // usernameのバリデーションを確認
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('user', [
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
        ])->postJson('user', [
            'name' => self::TESTING_NAME,
            'username' => '12345678901234567',
            'country_id' => self::TESTING_COUNTRY_ID,
        ]);

        $response->assertStatus(422);

        // 記号は`_` `-`以外は使用できない
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('user', [
            'name' => self::TESTING_NAME,
            'username' => self::TESTING_USERNAME.'@',
            'country_id' => self::TESTING_COUNTRY_ID,
        ]);

        $response->assertStatus(422);

        // 英字以外の文字は入力できない
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('user', [
            'name' => self::TESTING_NAME,
            'username' => 'あいうえお',
            'country_id' => self::TESTING_COUNTRY_ID,
        ]);

        $response->assertStatus(422);

        // 数字は登録できる
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('user', [
            'name' => self::TESTING_NAME,
            'username' => '000',
            'country_id' => self::TESTING_COUNTRY_ID,
        ]);

        $response->assertStatus(200);

        $this->deleteTestUser();
    }

    /**
     * Test `PUT:/user`
     *
     * @return void
     */
    public function test_updateUserProfile() :void
    {
        $additional_text = '_updated';

        // 正常系
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->putJson('user', [
            'name' => self::TESTING_NAME.$additional_text,
            'username' => self::TESTING_USERNAME.$additional_text,
            'country_id' => self::TESTING_COUNTRY_ID+10,
        ]);

        $response->assertStatus(200)->assertJson([
            "status" => true
        ]);

        // `username`に変更がなくてもリクエストは処理される
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->putJson('user', [
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
        ])->putJson('user', [
            'name' => self::TESTING_NAME,
            'country_id' => self::TESTING_COUNTRY_ID,
        ])->assertStatus(200)->assertJson([
            "status" => true
        ]);

        // すべての値が空でもリクエストは処理される
        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->putJson('user')->assertStatus(200)->assertJson([
            "status" => true
        ]);
    }

    /**
     * Test `DELETE:/user`
     *
     * @return void
     */
    public function test_deleteUserProfile()
    {
        // 正常系
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->deleteJson('user');

        $response->assertStatus(200)->assertJson([
            "status" => true
        ]);

        $this->getJson('/my-profile', [
            'Authorization' => 'Bearer '.$this->id_token
        ])->assertStatus(200)->assertJson([
            'status' => false,
            'message' => 'User profile is not registered',
            'code' => 2000,
        ]);

        // 削除後に再登録できるか
        $this->createAuth0User();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('user', [
            'name' => self::TESTING_NAME,
            'username' => self::TESTING_USERNAME,
            'country_id' => self::TESTING_COUNTRY_ID,
        ]);

        $response->assertStatus(200)->assertJson([
            "status" => true
        ]);
    }

    /**
     * Test `GET:/username/exists`
     *
     * @return void
     */
    public function test_duplicateUsername_exists()
    {
        $this->getJson('/username/exists')
        ->assertStatus(400);

        $this->getJson('/username/exists?username='.self::TESTING_USERNAME)
        ->assertStatus(200)->assertJson([
            'result' => true,
        ]);

        $this->deleteTestUser();

        $this->getJson('/username/exists?username='.self::TESTING_USERNAME)
        ->assertStatus(200)->assertJson([
            'result' => false,
        ]);
    }

    /**
     * Test `GET:/user/{username}`
     *
     * @return void
     */
    public function test_getUserProfileByUsername()
    {
        $this->getJson('/user/'.self::TESTING_USERNAME)
        ->assertStatus(200)
        ->assertJsonMissingExact(['auth_id'])
        ->assertJsonFragment(['username' => self::TESTING_USERNAME]);

        $this->getJson('/user/'.self::TESTING_USERNAME, [
            'Authorization' => 'Bearer '.$this->id_token
        ])
        ->assertStatus(200)
        ->assertJsonMissingExact(['auth_id'])
        ->assertJsonFragment(['username' => self::TESTING_USERNAME]);

        $this->getJson('/user/dummy')
        ->assertStatus(404);
    }
}
