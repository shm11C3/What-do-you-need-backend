<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePostRequest;
use App\Http\Requests\DeletePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Models\PostImage;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Ulid\Ulid;

class PostController extends Controller
{
    /**
     * @param User $user
     */
    public function __construct(Post $post, User $user)
    {
        $this->post = $post;
        $this->user = $user;
    }

    /**
     * 投稿をDBに登録
     * `POST: post`
     *
     * @todo `isValid_publish()`を追加する
     * @param CreatePostRequest $request
     * @return void
     */
    public function createPost(CreatePostRequest $request)
    {
        if(!$request->user){
            return abort(401);
        }
        $auth_id = $request->subject;

        // `is_draft`の値を検証
        if(!$this->post->isValid_isDraft($request['is_draft'], $request['is_publish']) ||
            !$this->post->isValid_publish($request['content'], $request['title'], $request['category_uuid'], $request['is_publish'])
        ){
            return abort(422);
        }

        $ulid = Ulid::generate();
        $image_group_uuid = $request['image_group_uuid'];

        try{
            DB::beginTransaction();

            DB::table('posts')->insert([
                'ulid' => $ulid,
                'auth_id' => $auth_id,
                'category_uuid' => $request['category_uuid'],
                'title' => $request['title'],
                'content' => $request['content'],
                'is_draft' => $request['is_draft'],
                'is_publish' => $request['is_publish'],
                'is_edited' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($image_group_uuid) {
                $postImage = new PostImage;
                $postImage->attachPostImageToPosts($auth_id, $image_group_uuid, $ulid);
            }

            DB::commit();
        }catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                "status" => false,
                "message" => config('app.debug') ? $e->getMessage() : '500 : '.HttpResponse::$statusTexts[500],
                500
            ]);
        }

        return response()->json(["status" => true, "ulid" => (string)$ulid]);
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

        $posts = Post::with('reactions')
        ->where('posts.is_deleted', 0)
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
            'posts.is_draft',
            'posts.is_publish',
            'posts.is_deleted',
            'posts.created_at',
            'posts.updated_at',
            'post_categories.uuid',
            'post_categories.name as category_name',
            'posts.is_edited',
            'users.name',
            'users.username',
            'users.country_id',
            'users.profile_img_uri',
            'users.delete_flg as is_deleted_user',
        ])
        ->simplePaginate(30);

        foreach($data as $i => $post) {
            $data[$i] = $this->post->addReactionElements($post, $auth_id);
        }

        return response()->json(["status" => true, $data]);
    }

    /**
     * `ulid`に一致する投稿を取得
     * `GET: posts`, `GET: posts/{category_uuid}`
     *
     * @param Request $request
     * @param string $ulid
     * @return void
     */
    public function getPost(Request $request, string $ulid)
    {
        // 引数が不正な場合は404を返す
        if(!is_ulid($ulid)){
            return abort(404);
        }

        $auth_id = $request->subject;

        $post = Post::with('reactions')
        ->where('ulid', $ulid)
        ->where('is_deleted', 0)
        ->where('delete_flg', 0);

        // ログイン時には自身の非公開投稿と下書きも取得する
        if($auth_id){
            $post = $post->where(function ($query) use ($auth_id) {
                $query->where('is_publish', 1)
                ->where('is_draft', 0)
                ->orWhere('users.auth_id', $auth_id);
            });
        }else{
            $post = $post->where('is_publish', 1)
            ->where('is_draft', 0);
        }
        $data = $post->join('users', 'users.auth_id', 'posts.auth_id')
        ->join('post_categories', 'post_categories.uuid', 'posts.category_uuid')
        ->get([
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
            'post_categories.name as category_name',
            'posts.is_edited',
            'users.name',
            'users.username',
            'users.country_id',
            'users.profile_img_uri',
            'users.delete_flg as is_deleted_user',
        ]);

        // 値が存在しない場合は404を返す
        if(!isset($data[0])){
            return abort(404);
        }

        $data = $this->post->addReactionElements($data[0], $auth_id);

        return response()->json(["status" => true, $data]);
    }

    /**
     * `ulid`に一致する投稿を更新
     * `PUT: /post`
     *
     * @param UpdatePostRequest $request
     * @return void
     */
    public function updatePost(UpdatePostRequest $request)
    {
        if(!$request->user){
            return abort(404);
        }

        $auth_id = $request->subject;
        $is_draft = $request['is_draft'];
        $is_publish = $request['is_publish'];
        $category_uuid = $request['category_uuid'];
        $content = $request['content'];
        $title = $request['title'];

        // `is_draft`の値を検証
        if(!$this->post->isValid_publish($content, $title, $category_uuid, $is_publish) ||
            !$this->post->isValid_isDraft($is_draft, $is_publish)
        ){
            return abort(422);
        }

        $ulid = $request['ulid'];

        // ulidを検証、権限がない場合は403;
        $post_info = $this->post->getPostInfo($ulid);

        if(!$post_info){
            return abort(404);
        }elseif($post_info->auth_id !== $auth_id){
            return abort(403);
        }

        $is_edited = $post_info->is_publish; // 更新前に投稿を公開していた場合`true`
        $image_group_uuid = $request['image_group_uuid'];

        try{
            DB::beginTransaction();

            DB::table('posts')->where('ulid', $ulid)->update([
                'category_uuid' => $request['category_uuid'],
                'title' => $request['title'],
                'content' => $request['content'],
                'is_draft' => $request['is_draft'],
                'is_publish' => $request['is_publish'],
                'is_edited' => $is_edited,
                'updated_at' => now()
            ]);

            if ($image_group_uuid) {
                $postImage = new PostImage;
                $postImage->attachPostImageToPosts($auth_id, $image_group_uuid, $ulid);
            }

            DB::commit();
        }catch(\Exception $e){
            DB::rollBack();

            return response()->json([
                "status" => false,
                "message" => config('app.debug') ? $e->getMessage() : '500 : '.HttpResponse::$statusTexts[500],
                500
            ]);
        }

        return response()->json(["status" => true, "ulid" => (string)$ulid]);
    }

    /**
     * Delete posts to which the user has access
     *
     * @param DeletePostRequest $request
     * @return response
     */
    public function deletePost(DeletePostRequest $request)
    {
        if(!$request->user){
            return abort(404);
        }

        $auth_id = $request->subject;
        $ulid = $request['ulid'];

        // ulidを検証、権限がない場合は403
        $post_info = $this->post->getPostInfo($ulid);

        if(!$post_info){
            return abort(404);
        }elseif($post_info->auth_id !== $auth_id){
            return abort(403);
        }

        try{
            DB::table('posts')->where('ulid', $ulid)->update([
                'is_deleted' => true
            ]);
        }catch(\Exception $e){
            return response()->json([
                "status" => false,
                "message" => config('app.debug') ? $e->getMessage() : '500 : '.HttpResponse::$statusTexts[500],
                500
            ]);
        }

        return response()->json(["status" => true, "ulid" => $ulid]);
    }

    /**
     * Retrieve and return a list of drafts created by authenticated user
     *
     * @param Request $request
     * @return response
     */
    public function getDrafts(Request $request)
    {
        $auth_id = $request->subject;

        $data = Post::where('posts.is_deleted', 0)
        ->where('is_draft', 1)
        ->where('auth_id', $auth_id)
        ->select([
            'posts.ulid',
            'posts.category_uuid',
            'posts.title',
            'posts.content',
            'posts.is_draft',
            'posts.is_publish',
            'posts.is_deleted',
            'posts.is_edited',
            'posts.created_at',
            'posts.updated_at',
        ])
        ->orderBy('updated_at', 'desc')
        ->simplePaginate(30);

        return response()->json(["status" => true, $data]);
    }

    /**
     * Get posts created by the specified user
     *
     * @param string $username
     * @param Request $request
     * @return response
     */
    public function getUserPosts(Request $request, string $username)
    {
        if (!preg_match('/^[A-Za-z\d_]+$/', $username)) {
            abort(400);
        }
        $auth_id = $request->subject;

        $target_auth_id = $this->user->getAuthIdByUsername($username);

        if ($target_auth_id == null) {
            // 存在しないユーザ名
            abort(404);
        }

        $posts = Post::with('reactions')
        ->where('posts.is_deleted', 0)
        ->where('posts.is_draft', 0)
        ->where('users.delete_flg', 0)
        ->where('posts.auth_id', $target_auth_id);

        if ($auth_id) {
            $posts->where(function ($query) use ($auth_id) {
                $query->where('is_publish', 1)
                ->orWhere('is_publish', 0)->where('users.auth_id', $auth_id);
            });
        } else {
            $posts->where('is_publish', 1);
        }

        $data = $posts->join('users', 'users.auth_id', 'posts.auth_id')
        ->join('post_categories', 'post_categories.uuid', 'posts.category_uuid')
        ->orderBy('posts.created_at' ,'desc')
        ->select([
            'posts.ulid',
            'posts.category_uuid',
            'posts.title',
            'posts.is_draft',
            'posts.is_publish',
            'posts.is_deleted',
            'posts.created_at',
            'posts.updated_at',
            'post_categories.name as category_name',
            'posts.is_edited',
            'users.name',
            'users.username',
            'users.country_id',
            'users.profile_img_uri',
            'users.delete_flg as is_deleted_user',
        ])
        ->simplePaginate(30);

        foreach($data as $i => $post) {
            $data[$i] = $this->post->addReactionElements($post, $auth_id);
        }

        return response()->json(["status" => true, $data]);
    }
}
