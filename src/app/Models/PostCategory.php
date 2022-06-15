<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostCategory extends Model
{
    use HasFactory;

    /**
     * カテゴリーの投稿を取得
     */
    public function posts()
    {
        return $this->hasMany(Post::class, 'uuid', 'category_uuid');
    }
}
