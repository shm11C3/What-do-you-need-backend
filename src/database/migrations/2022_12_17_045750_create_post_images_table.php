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
        Schema::create('post_images', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('image_group_uuid')->index();
            $table->ulid('post_ulid')->nullable()->index();
            $table->string('auth_id', 62)->index();
            $table->tinyInteger('image_number')->unsigned()->max(4);

            $table->unique(['post_ulid', 'image_group_uuid', 'image_number']);
            $table->unique(['auth_id', 'image_group_uuid', 'image_number']);

            $table->index(['post_ulid', 'image_number']);
            $table->index(['image_group_uuid', 'auth_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('post_images');
    }
};
