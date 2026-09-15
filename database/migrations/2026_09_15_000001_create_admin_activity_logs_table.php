<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_activity_logs')) {
            return;
        }

        Schema::create('admin_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('actor_name', 30);
            $table->string('actor_role', 20);
            $table->string('action', 60)->index();
            $table->string('section', 40)->index();
            $table->string('subject_type', 100)->nullable();
            $table->string('subject_id', 120)->nullable()->index();
            $table->string('subject_label')->nullable();
            $table->string('description', 500);
            $table->longText('before_values')->nullable();
            $table->longText('after_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_logs');
    }
};
