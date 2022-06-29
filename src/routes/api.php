<?php

use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserProfileController;
use App\Models\Post;

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

// 全ユーザ用エンドポイント（認証済みの場合 Request にユーザ情報を含み、ゲストの場合は何もしない）
Route::group(['middleware' => ['auth0:any']], function () {
    // PostController
    Route::get('/posts', [PostController::class, 'getPosts'])->name('getPosts');
    Route::get('/posts/{category}', [PostController::class, 'getPosts'])->name('getPosts')->whereUuid('category');

    Route::get('/post/{ulid}', [PostController::class, 'getPost'])->name('getPost')->whereAlphaNumeric('ulid');
});

// ログインユーザ用エンドポイント
Route::group(['middleware' => ['auth0:auth']], function () {

    // UserProfileController
    Route::get('/user/profile', [UserProfileController::class, 'getUserProfile'])->name('getUserProfile');

    Route::post('/user/create', [UserProfileController::class, 'storeUserProfile'])->name('storeUserProfile');

    Route::put('/user/update', [UserProfileController::class, 'updateUserProfile'])->name('updateUserProfile');

    Route::delete('/user/delete', [UserProfileController::class, 'deleteUserProfile'])->name('deleteUserProfile');

    // PostController
    Route::post('/post/create', [PostController::class, 'createPost']);

    Route::put('/post/update', [PostController::class, 'updatePost'])->name('updatePost');
//
//    Route::delete('post/delete/{ulid}', [PostController::class, 'deletePost'])->name('deletePost')->whereAlphaNumeric('ulid');
});
