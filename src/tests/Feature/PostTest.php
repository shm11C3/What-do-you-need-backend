<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostCategory;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    private $testing_post = [
        'ulid' => self::TESTING_POST_ULID,
        'category_uuid' => self::TESTING_CATEGORY_UUID,
        'title' => self::TESTING_POST_TITLE,
        'content' => self::TESTING_POST_CONTENT,
        'is_draft' => false,
        'is_publish' => true,
        'created_at' => null,
        'updated_at' => null,
        'auth_id' => null,
        "is_deleted" => false
    ];

    public function setup(): void
    {
        parent::setUp();
        $this->seed('DatabaseSeeder');
        $this->storeIdToken();
        $this->createTestUser();
        $this->testing_post['auth_id'] = $this->testing_auth_id;
        $this->testing_post['created_at'] = $this->testing_post['updated_at'] = date('c');
    }

    /**
     * Test `/posts`
     *
     * @return void
     */
    public function test_getPosts()
    {
        $this->createTestPost();

        // 非ログイン時
        $response = $this->getJson('/posts');

        $response->assertStatus(200)
        ->assertJsonMissingExact(['auth_id'])
        ->assertJsonMissingExact(['is_deleted' => 1])
        ->assertJsonMissingExact(['is_publish' => 0])
        ->assertJsonMissingExact(['is_draft' => 1])
        ->assertJsonMissingExact(['is_deleted_user' => 1]); // 削除されたユーザーの投稿は取得できない

        // ペジネーションのテスト
        $response = $this->getJson('/posts?page=2');
        $response->assertStatus(200)->assertJsonFragment(['current_page' => 2]);

        // ログイン時
        $response = $this->getJson('/posts', [
            'Authorization' => 'Bearer '.$this->id_token
        ]);
        $response->assertStatus(200)
        ->assertJsonFragment(['ulid' => self::TESTING_POST_ULID])
        ->assertJsonMissingExact(['is_deleted' => 1])
        ->assertJsonMissingExact(['is_deleted_user', 1])
        ->assertJsonMissingExact(['is_draft' => 1]); // 下書きに設定されたものはログインユーザ自身のものであっても取得できない

        // 非公開のものも自分で作成したものは取得される
        $this->toPrivate();

        $response = $this->getJson('/posts', [
            'Authorization' => 'Bearer '.$this->id_token
        ]);
        $response->assertJsonFragment(['ulid' => self::TESTING_POST_ULID, 'is_publish' => 0])
        ->assertJsonFragment(['is_publish' => 1]);

        $this->toPublish();

        // カテゴリを指定して取得
        $response = $this->get('/posts/'.self::TESTING_CATEGORY_UUID);
        $response->assertStatus(200)
        ->assertJsonFragment(['category_uuid' => self::TESTING_CATEGORY_UUID]);
        foreach(CategorySeeder::CATEGORIES as $category){
            $response->assertJsonMissingExact(['category_uuid' => $category['uuid']]); // 指定していないカテゴリは取得されない
        }
    }

    /**
     * テスト用ユーザーデータを作成
     *
     * @return void
     */
    private function createTestPost()
    {
        PostCategory::create([
            'uuid' => self::TESTING_CATEGORY_UUID,
            'name' => self::TESTING_CATEGORY_NAME,
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ]);

        Post::create($this->testing_post);
    }

    /**
     * テスト用ユーザーデータを削除
     *
     * @return void
     */
    private function refreshPost()
    {
        Post::where('ulid', self::TESTING_POST_ULID)->delete();
    }

    /**
     * テスト用ユーザーデータを論理削除
     *
     * @return void
     */
    private function softDelete()
    {
        Post::where('ulid', self::TESTING_POST_ULID)->update(['is_deleted' => 1]);
    }

    /**
     * テスト用ユーザーデータを非公開状態に変更
     *
     * @return void
     */
    private function toPrivate()
    {
        Post::where('ulid', self::TESTING_POST_ULID)->update(['is_publish' => 0]);
    }

    /**
     * テスト用ユーザーデータを公開状態に変更
     *
     * @return void
     */
    private function toPublish()
    {
        Post::where('ulid', self::TESTING_POST_ULID)->update(['is_publish' => 1]);
    }
}
