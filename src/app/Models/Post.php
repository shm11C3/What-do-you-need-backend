<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
