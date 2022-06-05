<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Consts\ErrorMessage;
use App\Models\User;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UserProfileController extends Controller
{
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
            return response()->json(ErrorMessage::ERROR_MESSAGE_LIST['user_already_exist'], 422);
        }

        try {
            DB::table('users')->insert([
                'auth_id'    => $request->subject,
                'name'       => $request['name'],
                'username'   => $request['username'],
                'country_id' => $request['country_id']
            ]);
        }catch (\Exception $e) {
            return response()->json(["status" => false, "message" => $e->getMessage(), 500]);
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
            return response()->json(ErrorMessage::ERROR_MESSAGE_LIST['user_does_not_exist']);
        }

        $user_data = $request->user[0];

        // `country_id`をもとに国名と国コードを追加
        $user_data->country_code = Country::COUNTRY_CODE_LIST[$request->user[0]->country_id];
        $user_data->country = Country::COUNTRY_LIST[$request->user[0]->country_id];

        return response()->json($user_data);
    }

    public function updateUserProfile(Request $request)
    {

    }

    public function deleteUserProfile(Request $request)
    {

    }
}
