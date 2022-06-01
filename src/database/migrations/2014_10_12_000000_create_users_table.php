<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('auth_id', 62)->primary();           // Auth0から取得した`user_id`
            $table->string('name', 45);                         // ユーザが登録する名前
            $table->string('username', 16)->unique();           // @usernameで表示される一意の文字列
            $table->unsignedSmallInteger('country_id');         // Countryモデルを参照
            $table->string('profile_img_uri', 255)->nullable(); // プロフィール画像を配信しているURI
            $table->boolean('delete_flg')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
