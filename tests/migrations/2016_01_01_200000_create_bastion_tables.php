<?php

use Ethereal\Bastion\Helper;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBastionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Helper::getAbilityTable(), function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name', 100);
            $table->string('title', 100)->nullable();
            $table->integer('entity_id')->unsigned()->nullable();
            $table->string('entity_type')->nullable();
            $table->timestamps();

            $table->unique(['name', 'entity_id', 'entity_type']);
        });

        Schema::create(Helper::getRoleTable(), function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name', 100)->unique();
            $table->string('title')->nullable();
            $table->boolean('system')->default(0)->comment('Is system role, should not be deleted.');
            $table->boolean('private')->default(0)->comment('Is not visible for users or lower level roles.');
            $table->integer('level')->unsigned()->default(1);
            $table->timestamps();
        });

        Schema::create(Helper::getAssignedRoleTable(), function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('role_id')->unsigned()->index();
            $table->morphs('target');
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on(Helper::getRoleTable())->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::create(Helper::getPermissionTable(), function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('ability_id')->unsigned()->index();
            $table->morphs('target');
            $table->integer('parent_id')->unsigned()->nullable();
            $table->string('parent_type')->nullable();
            $table->boolean('forbidden')->default(false);
            $table->string('group')->nullable();
            $table->timestamps();

            $table->foreign('ability_id')->references('id')->on(Helper::getAbilityTable())->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Helper::getPermissionTable());
        Schema::dropIfExists(Helper::getAssignedRoleTable());
        Schema::dropIfExists(Helper::getRoleTable());
        Schema::dropIfExists(Helper::getAbilityTable());
    }
}
