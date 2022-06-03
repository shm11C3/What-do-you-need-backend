<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserProfileController extends Controller
{
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function storeUserProfile(Request $request)
    {

    }

    /**
     * ミドルウェアで取得したユーザ情報を返す
     *
     * @param Request $request
     * @return void $user_data
     */
    public function getUserProfile(Request $request)
    {
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
