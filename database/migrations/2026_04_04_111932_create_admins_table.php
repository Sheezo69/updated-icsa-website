<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admins')) {
            return;
        }

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('username', 30)->unique('idx_username');
            $table->string('password_hash');
            $table->string('email', 190)->nullable();
            $table->timestamp('last_login')->nullable();
            $table->unsignedTinyInteger('login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->enum('role', ['admin', 'staff'])->default('staff');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
