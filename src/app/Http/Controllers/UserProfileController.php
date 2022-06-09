<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Consts\ErrorMessage;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UserProfileController extends Controller
{
    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * 受け取ったユーザーデータをDBに挿入する
     *
     * @param CreateUserRequest $request
     * @return void
     */
    public function storeUserProfile(CreateUserRequest $request)
    {
        // auth_idがすでに登録されている場合リターン
        if($request->user){
            return response()->json(ErrorMessage::MESSAGES['user_already_exist'], 422);
        }

        // 論理削除されたユーザの場合、レコードを更新する
        try {
            if($this->user->isDeletedUser($request->subject)){
                DB::table('users')->where('auth_id', $request->subject)->update([
                    'name'       => $request['name'],
                    'username'   => $request['username'],
                    'country_id' => $request['country_id'],
                    'delete_flg' => 0
                ]);
            }else{
                DB::table('users')->insert([
                    'auth_id'    => $request->subject,
                    'name'       => $request['name'],
                    'username'   => $request['username'],
                    'country_id' => $request['country_id']
                ]);
            }

        }catch (\Exception $e) {
            return response()->json([
                "status" => false,
                "message" => config('app.debug') ? $e->getMessage() : ErrorMessage::HTTP[500],
                500
            ]);
        }

        Cache::forget($request->subject);

        return response()->json(["status" => true]);
    }

    /**
     * ミドルウェアで取得したユーザ情報を返す
     *
     * @param Request $request
     * @return void $user_data
     */
    public function getUserProfile(Request $request)
    {
        if(!$request->user){
            return response()->json(ErrorMessage::MESSAGES['user_does_not_exist']);
        }

        $user_data = $request->user[0];

        // `country_id`をもとに国名と国コードを追加
        $user_data->country_code = Country::COUNTRY_CODE_LIST[$request->user[0]->country_id];
        $user_data->country = Country::COUNTRY_LIST[$request->user[0]->country_id];

        return response()->json($user_data);
    }

    /**
     * 受け取ったユーザーデータでDBを更新する
     *
     * @param UpdateUserRequest $request
     * @return void
     */
    public function updateUserProfile(UpdateUserRequest $request)
    {
        // ユーザーが登録されているか
        if(!$request->user){
            return response()->json(ErrorMessage::MESSAGES['user_does_not_exist']);
        }

        // 他のユーザーが同じ `username` を登録していないか
        if(!DB::table('users')->where('username', $request['username'])->where('auth_id', '!=', $request->subject)){
            return response()->json(ErrorMessage::MESSAGES['username_is_already_used'], 422);
        }

        // DBテーブルを更新
        try{
            DB::table('users')->where('auth_id', $request->subject)->update([
                'name'       => $request['name'],
                'username'   => $request['username'],
                'country_id' => $request['country_id']
            ]);
        }catch(\Exception $e){
            return response()->json([
                "status" => false,
                "message" => config('app.debug') ? $e->getMessage() : ErrorMessage::HTTP[500],
                500
            ]);
        }

        return response()->json(["status" => true]);
    }

    /**
     * ユーザーを削除
     *
     * @param Request $request
     * @return void
     */
    public function deleteUserProfile(Request $request)
    {
        // ユーザーが登録されているか
        if(!$request->user){
            return response()->json(ErrorMessage::MESSAGES['user_does_not_exist']);
        }

        // usersからユーザーを論理削除
        try{
            DB::table('users')->where('auth_id', $request->subject)->update([
                'username' => null,
                'delete_flg' => 1
            ]);

        }catch(\Exception $e){
            return response()->json([
                "status" => false,
                "message" => config('app.debug') ? $e->getMessage() : ErrorMessage::HTTP[500],
                500
            ]);
        }

        Cache::forget($request->subject);

        return response()->json(["status" => true]);
    }
}
