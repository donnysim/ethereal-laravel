<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TestsMigration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable()->references('id')->on('users')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('profile_user', function (Blueprint $table) {
            $table->integer('profile_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->boolean('test')->default(false);

            $table->foreign('profile_id')->references('id')->on('profiles')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->primary(['profile_id', 'user_id']);
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        Schema::create('articles_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('model_id');
            $table->string('locale', 6);

            $table->foreign('model_id')->references('id')->on('articles_translations')->onDelete('cascade');
            $table->unique(['model_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('profile_user');
        Schema::dropIfExists('profiles');
        Schema::dropIfExists('users');
    }
}
