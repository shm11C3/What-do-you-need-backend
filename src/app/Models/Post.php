<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Post extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ulid',
        'auth_id',
        'category_uuid',
        'title',
        'content',
        'is_draft',
        'is_publish',
        'is_deleted',
        'created_at',
        'updated_at',
    ];

    /**
     * 投稿のカテゴリーを取得　
     */
    public function category()
    {
        return $this->belongsTo(PostCategory::class, 'category_uuid', 'uuid');
    }

    /**
     * 投稿のユーザーを取得
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'auth_id', 'auth_id');
    }

    /**
     * `is_draft`と`is_publish`を検証
     *
     * @param boolean $is_draft
     * @param boolean $is_publish
     * @return boolean
     */
    public function isValid_isDraft(bool $is_draft, bool $is_publish): bool
    {
        return !($is_draft && $is_publish);
    }

    /**
     * `$is-publish`を検証する
     * `$content`, `$title`の値がnullの場合`$is_publish`はtrueを許可しない
     *
     * @param string $content
     * @param string $title
     * @param boolean $is_publish
     * @return boolean
     */
    public function isValid_publish(
        ?string $content,
        ?string $title,
        ?string $category_uuid,
        bool $is_publish
    ): bool {
        return (!$is_publish || ($content && $title && $category_uuid));
    }

     /**
      * `ulid`の投稿の投稿者`auth_id`取得する
      *
      * @param string $ulid
      * @return string|null
      */
    public function getPostOwner(string $ulid): ?string
    {
        $post_owner = DB::table('posts')->where('ulid', $ulid)->where('is_deleted', 0)->get('posts.auth_id');
        return $post_owner[0]->auth_id ?? null;
    }
}
