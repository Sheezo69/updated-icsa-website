<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('form_type', 40)->nullable();
        });
        Schema::create('inquiry_email_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_message_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('recipient')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('mailer', 40)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['contact_message_id', 'kind', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_email_attempts');
        Schema::table('contact_messages', fn (Blueprint $table) => $table->dropColumn('form_type'));
    }
};
