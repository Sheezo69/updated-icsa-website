<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_conversations')) {
            Schema::create('admin_conversations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('participant_one_id')->index();
                $table->unsignedBigInteger('participant_two_id')->index();
                $table->string('participant_one_name', 30);
                $table->string('participant_two_name', 30);
                $table->timestamp('last_message_at')->nullable()->index();
                $table->timestamps();
                $table->unique(['participant_one_id', 'participant_two_id'], 'admin_conversation_participants_unique');
            });
        }

        if (! Schema::hasTable('admin_messages')) {
            Schema::create('admin_messages', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('conversation_id')->index();
                $table->unsignedBigInteger('sender_id')->nullable()->index();
                $table->string('sender_name', 30);
                $table->text('body');
                $table->timestamp('read_at')->nullable()->index();
                $table->timestamps();
                $table->index(['conversation_id', 'id'], 'admin_messages_conversation_message_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_messages');
        Schema::dropIfExists('admin_conversations');
    }
};
