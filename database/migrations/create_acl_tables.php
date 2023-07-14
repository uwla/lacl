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
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('model_id')->nullable();
            $table->string('description')->nullable();
            $table->unique(['name', 'model', 'model_id']);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions_models', function (Blueprint $table) {
            $table->string('model');
            $table->string('model_id');
            $table->unsignedBigInteger('permission_id');
            $table->foreign('permission_id')
                  ->references('id')
                  ->on('permissions')
                  ->cascadeOnDelete();
            $table->primary(['model', 'model_id', 'permission_id']);
            $table->timestamps();
        });

        Schema::create('roles_models', function (Blueprint $table) {
            $table->string('model');
            $table->string('model_id');
            $table->unsignedBigInteger('role_id');
            $table->foreign('role_id')
                  ->references('id')
                  ->on('roles')
                  ->cascadeOnDelete();
            $table->primary(['model', 'model_id', 'role_id']);
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
        Schema::dropIfExists('permissions_models');
        Schema::dropIfExists('roles_models');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
