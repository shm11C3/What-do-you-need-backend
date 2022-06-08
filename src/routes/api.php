<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserProfileController;

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

    // UserProfileController
    Route::get('/user/profile', [UserProfileController::class, 'getUserProfile'])->name('getUserProfile');

    Route::post('/user/create', [UserProfileController::class, 'storeUserProfile'])->name('storeUserProfile');

    Route::put('/user/update', [UserProfileController::class, 'updateUserProfile'])->name('updateUserProfile');

    Route::delete('/user/delete', [UserProfileController::class, 'deleteUserProfile'])->name('deleteUserProfile');
});
