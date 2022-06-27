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

    private $post_data = [
        'category_uuid' => self::TESTING_CATEGORY_UUID,
        'title' => self::TESTING_POST_TITLE,
        'content' => self::TESTING_POST_CONTENT,
        'is_draft' => false,
        'is_publish' => true,
    ];

    private $testing_post = [
        'ulid' => self::TESTING_POST_ULID,
        'created_at' => null,
        'updated_at' => null,
        'auth_id' => null,
        "is_deleted" => false
    ];

    public function setup(): void
    {
        parent::setUp();
        $this->testing_post += $this->post_data;
        $this->seed('DatabaseSeeder');
        $this->storeIdToken();
        $this->createTestUser();
        $this->testing_post['auth_id'] = $this->testing_auth_id;
        $this->testing_post['created_at'] = $this->testing_post['updated_at'] = date('c');
    }

    /**
     * Test `/post/create`
     *
     * @return void
     */
    public function test_createPost()
    {
        $this->createTestCategory();

        $post_data = $this->testing_post;

        // バリデーションを通過する最大文字数でテスト
        $post_data['title'] = $this->generateRandStr(45);
        $post_data['content'] = $this->generateRandStr(4096);

        // 正常系
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('post/create', $post_data);

        $response->assertStatus(200)->assertJsonFragment(["status" => true])->assertSee('ulid');

        // バリデーション
        $post_data['category_uuid'] = 1;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('post/create', $post_data);

        $response->assertStatus(422);
        $post_data = $this->testing_post;

        $post_data['title'] = $this->generateRandStr(46);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('post/create', $post_data);
        $response->assertStatus(422);

        $post_data['content'] = $this->generateRandStr(4097);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('post/create', $post_data);
        $response->assertStatus(422);

        $post_data = $this->testing_post;

        $post_data['is_publish'] = $post_data['is_draft'] = true;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('post/create', $post_data);
        $response->assertStatus(422);
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

        $this->refreshPost();
    }

    /**
     * Test `/post/{ulid}`
     *
     * @return void
     */
    public function test_getPost()
    {
        $this->createTestPost();

        $this->getJson('/post')->assertStatus(404);
        $this->getJson('/post/01234567')->assertStatus(404);
        $this->getJson('/post/01G68PASA5MMA0B1EHBDDDWBP5')->assertStatus(404);

        // ログインせずにアクセス
        $response = $this->getJson('/post/'.self::TESTING_POST_ULID);
        $response->assertStatus(200)
        ->assertJsonFragment(['ulid' => self::TESTING_POST_ULID]);

        // ログインしてアクセス
        $response = $this->getJson('/post/'.self::TESTING_POST_ULID, [
            'Authorization' => 'Bearer '.$this->id_token
        ]);
        $response->assertStatus(200)
        ->assertJsonFragment(['ulid' => self::TESTING_POST_ULID]);

        // ログインして他人の非公開投稿にアクセス
        $response = $this->getJson('/post/'.$this->getPrivatePost(), [
            'Authorization' => 'Bearer '.$this->id_token
        ])->assertStatus(404);

        /**
         * 非公開投稿
         */
        $this->toPrivate();

        // 投稿所有アカウントにログインしてアクセス
        $response = $this->getJson('/post/'.self::TESTING_POST_ULID, [
            'Authorization' => 'Bearer '.$this->id_token
        ]);
        $response->assertStatus(200)
        ->assertJsonFragment(['ulid' => self::TESTING_POST_ULID, 'is_publish' => 0]);

        // ログインせずにアクセス
        $response = $this->getJson('/post/'.self::TESTING_POST_ULID)
        ->assertStatus(404);

        $this->toPublish();

        /**
         * 下書き状態
         */
        $this->toDraft();

        // 投稿所有アカウントにログインしてアクセス
        $response = $this->getJson('/post/'.self::TESTING_POST_ULID, [
            'Authorization' => 'Bearer '.$this->id_token
        ]);
        $response->assertStatus(200)
        ->assertJsonFragment(['ulid' => self::TESTING_POST_ULID, 'is_draft' => 1]);

        // ログインせずにアクセス
        $response = $this->getJson('/post/'.self::TESTING_POST_ULID)
        ->assertStatus(404);

        /**
         * 下書き・非公開状態
         */
        $this->toPrivate();

        // 投稿所有アカウントにログインしてアクセス
        $response = $this->getJson('/post/'.self::TESTING_POST_ULID, [
            'Authorization' => 'Bearer '.$this->id_token
        ]);
        $response->assertStatus(200)
        ->assertJsonFragment(['ulid' => self::TESTING_POST_ULID, 'is_draft' => 1]);

        // ログインせずにアクセス
        $response = $this->getJson('/post/'.self::TESTING_POST_ULID)
        ->assertStatus(404);

        /**
         * 投稿を論理削除
         */
        $this->softDelete();

        // 投稿所有アカウントにログインしてアクセス
        $response = $this->getJson('/post/'.self::TESTING_POST_ULID, [
            'Authorization' => 'Bearer '.$this->id_token
        ])->assertStatus(404);

        // ログインせずにアクセス
        $response = $this->getJson('/post/'.self::TESTING_POST_ULID)
        ->assertStatus(404);

        $this->backFromSoftDelete();

        /**
         * ユーザーを削除
         */
        $this->softDeleteUser();

        $response = $this->getJson('/post/'.self::TESTING_POST_ULID)
        ->assertStatus(404);

        $this->refreshPost();
        $this->backFromSoftDeleteUser();
    }

    /**
     * テスト用投稿データを作成
     *
     * @return void
     */
    private function createTestPost()
    {
        $this->createTestCategory();
        Post::create($this->testing_post);
    }

    /**
     * テスト用カテゴリを作成
     *
     * @return void
     */
    private function createTestCategory()
    {
        PostCategory::create([
            'uuid' => self::TESTING_CATEGORY_UUID,
            'name' => self::TESTING_CATEGORY_NAME,
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ]);
    }

    /**
     * テスト用投稿データを削除
     *
     * @return void
     */
    private function refreshPost()
    {
        Post::where('ulid', self::TESTING_POST_ULID)->delete();
    }

    /**
     * テスト用投稿データを論理削除
     *
     * @return void
     */
    private function softDelete()
    {
        Post::where('ulid', self::TESTING_POST_ULID)->update(['is_deleted' => 1]);
    }

    /**
     * テスト用投稿データを論理削除
     *
     * @return void
     */
    private function backFromSoftDelete()
    {
        Post::where('ulid', self::TESTING_POST_ULID)->update(['is_deleted' => 0]);
    }

    /**
     * テスト用投稿データを非公開状態に変更
     *
     * @return void
     */
    private function toPrivate()
    {
        Post::where('ulid', self::TESTING_POST_ULID)->update(['is_publish' => 0]);
    }

    /**
     * テスト用投稿データを公開状態に変更
     *
     * @return void
     */
    private function toPublish()
    {
        Post::where('ulid', self::TESTING_POST_ULID)->update(['is_publish' => 1]);
    }

    /**
     * テスト用投稿データを下書状態に変更
     *
     * @return void
     */
    private function toDraft()
    {
        Post::where('ulid', self::TESTING_POST_ULID)->update(['is_draft' => 1]);
    }

    /**
     * テスト用投稿データを下書きから戻す
     *
     * @return void
     */
    private function backFromDraft()
    {
        Post::where('ulid', self::TESTING_POST_ULID)->update(['is_draft' => 0]);
    }

    /**
     * テストユーザー以外が作成した非公開投稿を取得
     *
     * @return string $ulid
     */
    private function getPrivatePost(): string
    {
        $private_post = Post::where('ulid', '!=', self::TESTING_POST_ULID)
        ->where('is_publish', 0)
        ->limit(1)
        ->get('ulid');

        return (string)$private_post[0]->ulid;
    }
}
