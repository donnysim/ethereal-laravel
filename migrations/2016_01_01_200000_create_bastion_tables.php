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
        Schema::create(Helper::abilitiesTable(), function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('entity_id')->unsigned()->nullable();
            $table->string('entity_type')->nullable();
            $table->timestamps();

            $table->unique(['name', 'entity_id', 'entity_type']);
        });

        Schema::create(Helper::rolesTable(), function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('title')->nullable();
            $table->boolean('system')->default(0)->comment('Is system role, should not be deleted.');
            $table->boolean('private')->default(0)->comment('Is not visible for users or lower level roles.');
            $table->integer('level')->default(1);
            $table->timestamps();
        });

        Schema::create(Helper::assignedRolesTable(), function (Blueprint $table) {
            $table->integer('role_id')->unsigned()->index();
            $table->morphs('entity');

            $table->foreign('role_id')->references('id')->on(Helper::rolesTable())
                ->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::create(Helper::permissionsTable(), function (Blueprint $table) {
            $table->integer('ability_id')->unsigned()->index();
            $table->morphs('entity');
            $table->boolean('forbidden')->default(false);

            $table->foreign('ability_id')->references('id')->on(Helper::abilitiesTable())->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Helper::permissionsTable());
        Schema::dropIfExists(Helper::assignedRolesTable());
        Schema::dropIfExists(Helper::rolesTable());
        Schema::dropIfExists(Helper::abilitiesTable());
    }
}
