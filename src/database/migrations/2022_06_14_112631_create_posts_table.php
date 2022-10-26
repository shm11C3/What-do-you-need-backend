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
        Schema::create('posts', function (Blueprint $table) {
            $table->string('ulid', 26)->primary();
            $table->string('auth_id', 62)->index();
            $table->uuid('category_uuid')->index();
            $table->string('title', 45)->index()->nullable();
            $table->string('content', 4096)->nullable();
            $table->boolean('is_draft')->default(false)->index();
            $table->boolean('is_publish')->default(false)->index();
            $table->boolean('is_deleted')->default(false)->index();
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
        Schema::dropIfExists('posts');
    }
};
