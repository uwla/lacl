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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("model")->nullable();
            $table->string("model_id")->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("description");
            $table->timestamps();
        });

        Schema::create('roles_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger("role_id");
            $table->unsignedBigInteger("permission_id");
            $table->timestamps();
            $table->foreign('role_id')
                  ->references('id')
                  ->on('roles')
                  ->cascadeOnDelete();
            $table->foreign('permission_id')
                  ->references('id')
                  ->on('permissions')
                  ->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('users_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->foreign('role_id')
                  ->references('id')
                  ->on('roles')
                  ->cascadeOnDelete();
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
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
        Schema::dropIfExists('users_roles');
        Schema::dropIfExists('roles_permissions');
		Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
