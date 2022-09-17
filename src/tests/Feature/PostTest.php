<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\PostCategory;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Ulid\Ulid;

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

        $post_data = $this->testing_post;

        // 削除されたユーザーでは作成できない
        $this->softDeleteUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->postJson('post/create', $post_data);

        $response->assertStatus(401);

        $this->backFromSoftDeleteUser();
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
     * Test `/post/update`
     *
     * @return void
     */
    public function test_updatePost()
    {
        $this->createTestPost();

        // 更新前の投稿
        $post_before_updated = [
            'category_uuid' => self::TESTING_CATEGORY_UUID,
            'title' => self::TESTING_POST_TITLE,
            'content' => self::TESTING_POST_CONTENT,
            'is_draft' => 0,
            'is_publish' => 1,
        ];

        $ulid = self::TESTING_POST_ULID;
        $post_data = ['ulid' => $ulid] + $this->post_data;
        $post_data['content'] = 'Updated post';

        // 正常系
        $this->getJson('/post/'.self::TESTING_POST_ULID) // 更新前
        ->assertJsonFragment(["content" => self::TESTING_POST_CONTENT]);

        $response = $this->updatePost($post_data);
        $response->assertStatus(200)
        ->assertJsonFragment(["status" => true]);

        $this->getJson('/post/'.self::TESTING_POST_ULID) // 更新後
        ->assertJsonFragment([
            "content" => 'Updated post',
            "is_edited" => 1,
        ]);

        $this->regeneratePost();

        // バリデーションテスト
        $post_data['ulid'] = 'test';
        $response = $this->updatePost($post_data);
        $response->assertStatus(422);
        $post_data['ulid'] = $ulid;

        $post_data['is_draft'] = $post_data['is_publish'] = true;
        $response = $this->updatePost($post_data);
        $response->assertStatus(422);
        $post_data = ['ulid' => $ulid] + $this->post_data;

        $post_data['title'] = '';
        $response = $this->updatePost($post_data);
        $response->assertStatus(422);
        $post_data = ['ulid' => $ulid] + $this->post_data;

        $post_data['content'] = '';
        $response = $this->updatePost($post_data);
        $response->assertStatus(422);
        $post_data = ['ulid' => $ulid] + $this->post_data;

        $post_data['title'] = $post_data['content']= '';
        $response = $this->updatePost($post_data);
        $response->assertStatus(422);
        $post_data = ['ulid' => $ulid] + $this->post_data;

        $this->getJson('/post/'.self::TESTING_POST_ULID) // 更新されない
        ->assertJsonFragment($post_before_updated);

        // 他人の投稿は改変できない
        $post_data['ulid'] =  $this->getOtherUserPost($this->testing_auth_id);
        $response = $this->updatePost($post_data);
        $response->assertStatus(403);

        $this->getJson('/post/'.self::TESTING_POST_ULID) // 更新されない
        ->assertJsonFragment($post_before_updated);

        // 存在しないulidでは投稿できない
        $post_data['ulid'] =  (string)Ulid::generate();
        $response = $this->updatePost($post_data);
        $response->assertStatus(404);

        $this->getJson('/post/'.self::TESTING_POST_ULID) // 更新されない
        ->assertJsonFragment($post_before_updated);

        $post_data = ['ulid' => $ulid] + $this->post_data;

        // ログインぜずにアクセス
        $response = $this->withHeaders([
            'Authorization' => null
        ])->putJson('post/update', $post_data);
        $response = $this->putJson('post/update', $post_data);
        $response->assertStatus(401);

        $this->getJson('/post/'.self::TESTING_POST_ULID) // 更新されない
        ->assertJsonFragment($post_before_updated);

        // 削除された投稿は改変できない
        $this->softDelete();

        $response = $this->updatePost($post_data);
        $response->assertStatus(404);

        $this->backFromSoftDelete();

        // 削除されたユーザーの投稿は改変できない
        $this->softDeleteUser();

        $response = $this->updatePost($post_data);
        $response->assertStatus(404);

        $this->backFromSoftDeleteUser();
    }

    /**
     * 'post/update'に$post_dataをPUTする
     *
     * @param array $post_data
     * @return object
     */
    private function updatePost(array $post_data): object
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->putJson('post/update', $post_data);
    }

    public function test_deletePost()
    {
        $this->regeneratePost();

        // 正常系
        $ulid = self::TESTING_POST_ULID;
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->deleteJson('post/delete', ['ulid' => $ulid]);

        $response->assertStatus(200)->assertJsonFragment(["status" => true])->assertSee('ulid');

        // 削除されているか確認
        $this->getJson('/post/'.self::TESTING_POST_ULID)
        ->assertStatus(404);

        $this->withHeaders(['Authorization' => 'Bearer '.$this->id_token])
        ->updatePost(['ulid' => $ulid] + $this->post_data)
        ->assertStatus(404);

        $this->withHeaders(['Authorization' => 'Bearer '.$this->id_token])
        ->deleteJson('post/delete', ['ulid' => $ulid])
        ->assertStatus(404);

        $this->regeneratePost();

        // 削除されたユーザーは投稿削除できない
        $this->softDeleteUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->deleteJson('post/delete', ['ulid' => $ulid]);

        $response->assertStatus(404);

        $this->backFromSoftDeleteUser();

        // 他人の投稿は削除できない
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->deleteJson('post/delete', ['ulid' => $this->getOtherUserPost($this->testing_auth_id)]);

        $response->assertStatus(403);

        // 存在しない投稿は削除できない
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->deleteJson('post/delete', ['ulid' => (string)Ulid::generate()]);

        $response->assertStatus(404);
    }

    /**
     * Test `/post/drafts`
     *
     * @return void
     */
    public function test_getDrafts(): void
    {
        $this->regeneratePost();
        $this->toDraft();
        $this->toPrivate();

        $other_user_ost_ulid = $this->getOtherUserPost($this->testing_auth_id);

        Post::where('ulid', $other_user_ost_ulid)->update(['is_draft' => 1, 'is_publish' => 0]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->id_token
        ])->getJson('post/drafts');

        $response->assertOk()
        ->assertJsonMissingExact(['is_deleted' => 1])
        ->assertJsonMissingExact(['is_draft' => 0])
        ->assertJsonFragment(['is_draft' => 1])
        ->assertJsonMissing(['ulid' => $other_user_ost_ulid]);
    }

    /**
     * Test `/{username}/posts`
     *
     * @return void
     */
    public function test_getUserPosts(): void
    {
        $this->regeneratePost();

        Post::create([
            'ulid' => Ulid::generate(false),
            'auth_id' => 'dummy',
            'category_uuid' => self::TESTING_CATEGORY_UUID,
            'title' => 'Cannot be get',
            'content' => 'Cannot be get',
            'is_draft' => 0,
            'is_publish' => 1,
            'is_edited' => 0,
            'is_deleted' => 0,
        ]);

        User::create([
            'auth_id' => 'dummy',
            'name' => 'dummy',
            'username' => 'dummy',
            'country_id' => 1120,
        ]);

        // 非ログイン時
        $response = $this->getJson('/'.self::TESTING_USERNAME.'/posts');

        $response->assertStatus(200)
        ->assertJsonMissingExact(['auth_id'])
        ->assertJsonMissingExact(['is_deleted' => 1])
        ->assertJsonMissingExact(['is_publish' => 0])
        ->assertJsonMissingExact(['is_draft' => 1])
        ->assertJsonMissingExact(['is_deleted_user' => 1])
        ->assertJsonMissingExact(['username' => 'dummy'])
        ->assertJsonFragment(['username' => self::TESTING_USERNAME]);

        // ログイン時
        $response = $this->getJson('/'.self::TESTING_USERNAME.'/posts', [
            'Authorization' => 'Bearer '.$this->id_token
        ]);
        $response->assertStatus(200)
        ->assertJsonFragment(['ulid' => self::TESTING_POST_ULID])
        ->assertJsonMissingExact(['is_deleted' => 1])
        ->assertJsonMissingExact(['is_deleted_user', 1])
        ->assertJsonMissingExact(['is_draft' => 1]) // 下書きに設定されたものはログインユーザ自身のものであっても取得できない
        ->assertJsonMissingExact(['username' => 'dummy']);

        // 非公開のものも自分で作成したものは取得される
        $this->toPrivate();

        $response = $this->getJson('/'.self::TESTING_USERNAME.'/posts', [
            'Authorization' => 'Bearer '.$this->id_token
        ]);
        $response->assertJsonFragment(['ulid' => self::TESTING_POST_ULID, 'is_publish' => 0]);
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
     * テスト用投稿データを削除
     *
     * @return void
     */
    private function refreshPost()
    {
        Post::where('ulid', self::TESTING_POST_ULID)->delete();
        PostCategory::where('uuid', self::TESTING_CATEGORY_UUID)->delete();
    }

    /**
     * テスト用データを再生成
     *
     * @return void
     */
    private function regeneratePost()
    {
        $this->refreshPost();
        $this->createTestPost();
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

    /**
     * 他人が作成した投稿のulidを取得
     *
     * @param string $auth_id
     * @return string $other_user_post_ulid
     */
    private function getOtherUserPost(string $auth_id): string
    {
        $other_user_post = Post::where('auth_id', '!=', $auth_id)
        ->where('is_deleted', 0)
        ->limit(1)
        ->get('ulid');

        return (string)$other_user_post[0]->ulid;
    }
}
