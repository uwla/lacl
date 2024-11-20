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
            $table->string('model_type')->nullable();
            $table->string('model_id')->nullable();
            $table->string('description')->nullable();
            $table->unique(['name', 'model_type', 'model_id']);
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissionables', function (Blueprint $table) {
            $table->string('permissionable_type');
            $table->string('permissionable_id');
            $table->unsignedBigInteger('permission_id');
            $table->foreign('permission_id')
                  ->references('id')
                  ->on('permissions')
                  ->cascadeOnDelete();
            $table->primary([
                'permissionable_type',
                'permissionable_id',
                'permission_id',
            ]);
            $table->timestamps();
        });

        Schema::create('roleables', function (Blueprint $table) {
            $table->string('roleable_type');
            $table->string('roleable_id');
            $table->unsignedBigInteger('role_id');
            $table->foreign('role_id')
                  ->references('id')
                  ->on('roles')
                  ->cascadeOnDelete();
            $table->primary(['roleable_type', 'roleable_id', 'role_id']);
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
        Schema::dropIfExists('permissionables');
        Schema::dropIfExists('roleables');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};