<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'auth_id',
        'name',
        'username',
        'country_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
		'updated_at',
		'delete_flg',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [

    ];

    /**
     * ユーザが論理削除されているかをDBから取得して確認
     *
     * @param object $u
     * @return boolean
     */
    public function isDeletedUser(string $auth_id): bool
    {
        return DB::table('users')->where('auth_id', $auth_id)->where('delete_flg', 1)->exists();
    }

    /**
     * $requestsから受け取った配列の存在する各値のみを配列に追加して返す
     *
     * @param array $request
     * @return array
     */
    public function mergeUpdateUserData(array $requests): array
    {
        $user_data = [];

        if($requests['name']){
            $user_data = $user_data + ['name' => $requests['name']];
        }
        if($requests['username']){
            $user_data = $user_data + ['username' => $requests['username']];
        }
        if($requests['country_id']){
            $user_data = $user_data + ['country_id' => $requests['country_id']];
        }

        return $user_data;
    }
}
