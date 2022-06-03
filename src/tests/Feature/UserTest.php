<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;

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
     * @return void
     */
    public function test_getUserProfile(): void
    {
        $response = $this->get('/profile');
        $response->assertStatus(401)->assertJson(["code" => 1000]);

        $response = $this->get('/profile', [
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
    }

    /**
     * テスト用ユーザを作成
     *
     * @return void
     */
    private function createTestUser(): void
    {
        $decoded_id_token = json_decode(base64_decode(explode('.', $this->id_token)[1]));
        $this->testing_auth_id = $decoded_id_token->sub;

        User::create([
            'auth_id' => $this->testing_auth_id,
            'name' => self::TESTING_NAME,
            'username' => self::TESTING_USERNAME,
            'country_id' => self::TESTING_COUNTRY_ID,
        ]);
    }
}
