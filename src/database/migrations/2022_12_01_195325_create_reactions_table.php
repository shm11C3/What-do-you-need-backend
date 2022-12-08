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
        Schema::create('reactions', function (Blueprint $table) {
            $table->ulid('ulid')->primary();
            $table->string('auth_id', 62)->index();
            $table->ulid('reactable_ulid')->index();
            $table->string('reaction_type', 62)->index();
            $table->string('reactable_type')->index();
            $table->index(['auth_id', 'reaction_type']);
            $table->index(['reactable_ulid', 'reaction_type']);
            $table->index(['reactable_ulid', 'reactable_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reactions');
    }
};
