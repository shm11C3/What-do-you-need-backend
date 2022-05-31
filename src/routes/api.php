<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/', function () {
    return response()->json(["status"=>200]);
});

// ログインユーザ用エンドポイント
Route::group(['middleware' => ['auth0']], function () {
    Route::get('/example', function (Request $request) {
        return response()->json([
            "autho_user_id" => $request['auth0_user_id'],
            "message" => "ok"
        ]);
    });
});
