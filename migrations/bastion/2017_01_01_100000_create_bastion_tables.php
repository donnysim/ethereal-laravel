<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBastionTables extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('assigned_permissions');
        Schema::dropIfExists('assigned_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }

    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name', 100);
            $table->string('title', 100)->nullable();
            $table->string('guard', 100)->default('default');
            $table->boolean('system')->default(0)->comment('Is system role, should not be deleted.');
            $table->boolean('private')->default(0)->comment('Is not visible for users or lower level roles.');
            $table->integer('level')->unsigned()->default(1)->comment('Lower level means higher role.');
            $table->timestamps();

            $table->unique(['name', 'guard']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name', 100);
            $table->string('title', 100)->nullable();
            $table->string('guard', 100)->default('default');
            $table->nullableMorphs('model');
            $table->timestamps();

            $table->unique(['name', 'guard', 'model_id', 'model_type']);
        });

        Schema::create('assigned_permissions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->unsignedInteger('permission_id');
            $table->morphs('model');

            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('CASCADE');
            $table->primary(['permission_id', 'model_id', 'model_type']);
            $table->unique(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('assigned_roles', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->unsignedInteger('role_id');
            $table->morphs('model');

            $table->foreign('role_id')->references('id')->on('roles')->onDelete('CASCADE');
            $table->primary(['role_id', 'model_id', 'model_type']);
            $table->unique(['role_id', 'model_id', 'model_type']);
        });
    }
}
