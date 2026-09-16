<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_conversations')) {
            foreach (['participant_one_archived_at', 'participant_two_archived_at', 'participant_one_pinned_at', 'participant_two_pinned_at'] as $column) {
                if (! Schema::hasColumn('admin_conversations', $column)) {
                    Schema::table('admin_conversations', fn (Blueprint $table) => $table->timestamp($column)->nullable());
                }
            }
        }

        if (Schema::hasTable('admin_messages')) {
            foreach (['attachment_path' => 255, 'attachment_name' => 255, 'attachment_mime' => 80] as $column => $length) {
                if (! Schema::hasColumn('admin_messages', $column)) {
                    Schema::table('admin_messages', fn (Blueprint $table) => $table->string($column, $length)->nullable());
                }
            }
            if (! Schema::hasColumn('admin_messages', 'attachment_size')) {
                Schema::table('admin_messages', fn (Blueprint $table) => $table->unsignedInteger('attachment_size')->nullable());
            }
        }
    }

    public function down(): void
    {
        // Preserve message metadata and inbox preferences on rollback.
    }
};
