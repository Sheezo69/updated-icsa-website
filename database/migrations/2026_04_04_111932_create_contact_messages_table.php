<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contact_messages')) {
            return;
        }

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 190)->index('idx_contact_email');
            $table->string('phone', 40);
            $table->string('course_interest', 120)->nullable();
            $table->string('subject', 60)->nullable();
            $table->text('message')->nullable();
            $table->timestamp('created_at')->useCurrent()->index('idx_contact_created_at');
            $table->enum('status', ['new', 'in_progress', 'resolved', 'archived'])->default('new');
            $table->text('admin_notes')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
