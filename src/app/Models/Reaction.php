<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Support\Facades\DB;

class Reaction extends Model
{
    use HasFactory;
    use HasUlids;

    protected $primaryKey = 'ulid';

    protected $keyType = 'string';

    /**
     * モデルのIDを自動増分するか
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ulid',
        'auth_id',
        'reactable_ulid',
        'reaction_type',
        'reactable_type',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public const TYPES = [
        'good' => '👍',
        'more detail' => '🧐',
        'smiling face with heart-eyes' => '🥰',
        'raised hand' => '✋',
        'congratulations' => '🎉',
        'unique idea' => '🚀',
        'hot' => '🔥',
        'perfect' => '💯',
        'red heart' => '❤',
        'orange heart' => '🧡',
        'yellow heart' => '💛',
        'green heart' => '💚',
        'blue heart' => '💙',
        'purple heart' => '💜',
        'brown heart' => '🤎',
        'black heart' => '🖤',
        'white heart' => '🤍',
    ];

    /**
     * reactableな親モデルの取得（投稿かコメント）
     */
    public function reactable()
    {
        return $this->morphTo('reactable', 'reactable_type');
    }

    /**
     * 同一の投稿に対して同じユーザからの同じリアクションが存在しない場合、Trueを返す
     *
     * Comment追加時に引数`reactableType`を追加し、検証するテーブルを分岐させる
     *
     * @param string $reactable_ulid
     * @param string $auth_id
     * @param string $reaction_type
     * @return boolean
     */
    public function isUniqueReactions(
        string $reactable_ulid,
        string $auth_id,
        string $reaction_type,
    ): bool
    {
        return !DB::table('reactions')
            ->where('reactable_ulid', $reactable_ulid)
            ->where('auth_id', $auth_id)
            ->where('reaction_type', $reaction_type)
            ->exists();
    }
}
