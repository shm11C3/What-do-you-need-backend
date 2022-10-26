<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
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
    #Route::get('/posts/{category}', [PostController::class, 'getPosts'])->name('getPosts')->whereUuid('category');
    // TODO カテゴリ、ユーザー名などの条件はクエリで指定

    Route::get('/post/{ulid}', [PostController::class, 'getPost'])->name('getPost')->where('ulid', '[0-9a-hjkmnp-zA-HJKMNP-Z]{26}');
    Route::get('/post', function () {
        abort(404);
    });

    Route::get('/username/exists', [UserProfileController::class, 'duplicateUsername_exists']);

    Route::get('/categories', [CategoryController::class, 'getCategories']);

    Route::get('/posts/{username}', [PostController::class, 'getUserPosts'])->name('getUserPost');

    Route::get('/user/{username}', [UserProfileController::class, 'getUserProfileByUsername'])->name('getUserProfile');
});

// ログインユーザ用エンドポイント
Route::group(['middleware' => ['auth0:auth']], function () {

    // UserProfileController
    Route::get('/my-profile', [UserProfileController::class, 'getUserProfile'])->name('getMyProfile');

    Route::post('/user', [UserProfileController::class, 'storeUserProfile'])->name('storeUserProfile');

    Route::put('/user', [UserProfileController::class, 'updateUserProfile'])->name('updateUserProfile');

    Route::delete('/user', [UserProfileController::class, 'deleteUserProfile'])->name('deleteUserProfile');

    // PostController
    Route::post('/post', [PostController::class, 'createPost']);

    Route::put('/post', [PostController::class, 'updatePost'])->name('updatePost');

    Route::delete('/post', [PostController::class, 'deletePost'])->name('deletePost');

    Route::get('/post/drafts', [PostController::class, 'getDrafts'])->name('getDrafts');

    // AuthController
    Route::post('/auth/change-password', [AuthController::class, 'requestResetPasswordMail']);

    Route::post('auth/resend-verification-email', [AuthController::class, 'resendVerificationEmail']);
});
