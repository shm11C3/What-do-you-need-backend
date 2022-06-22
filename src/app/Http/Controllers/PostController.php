<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ulid\Ulid;
use Tests\Feature\PostTest;

class PostController extends Controller
{
    /**
     * @param User $user
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * 投稿の一覧を取得
     * `GET: posts`, `GET: posts/{category_uuid}`
     *
     * @param Request $request
     * @param string|null $category_uuid
     * @return response
     */
    public function getPosts(Request $request, string $category_uuid = null)
    {
        $auth_id = $request->subject;

        $posts = Post::where('posts.is_deleted', 0)
        ->where('posts.is_draft', 0)
        ->where('users.delete_flg', 0);

        // ログイン時には自身の非公開投稿も取得する
        if($auth_id){
            $posts->where(function ($query) use ($auth_id) {
                $query->where('is_publish', 1)
                ->orWhere('is_publish', 0)->where('users.auth_id', $auth_id);
            });
        }else{
            $posts->where('is_publish', 1);
        }

        // カテゴリを指定された場合where句を追加
        if($category_uuid){
            $posts->where('posts.category_uuid', $category_uuid);
        }

        $data = $posts->join('users', 'users.auth_id', 'posts.auth_id')
        ->join('post_categories', 'post_categories.uuid', 'posts.category_uuid')
        ->orderBy('posts.created_at' ,'desc')
        ->select([
            'posts.ulid',
            'posts.category_uuid',
            'posts.title',
            'posts.content',
            'posts.is_draft',
            'posts.is_publish',
            'posts.is_deleted',
            'posts.created_at',
            'posts.updated_at',
            'post_categories.uuid',
            'post_categories.name',
            'users.name',
            'users.username',
            'users.country_id',
            'users.profile_img_uri',
            'users.delete_flg as is_deleted_user',
        ])
        ->simplePaginate(30);

        return response()->json(["status" => true, $data]);
    }
}
