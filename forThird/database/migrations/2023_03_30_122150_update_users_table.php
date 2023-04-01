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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('login_type', ['email', 'apple'])->default('email');

            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->renameColumn('name', 'login');
            $table->dropColumn('email_verified_at');

            $table->unique(['login', 'login_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['login', 'login_type']);
            $table->dropColumn('login_type');

            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
            $table->renameColumn('login', 'name');
            $table->timestamp('email_verified_at')->nullable();
        });
    }
};
