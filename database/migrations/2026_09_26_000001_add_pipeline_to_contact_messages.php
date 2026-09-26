<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contact_messages')) {
            return;
        }

        if (! Schema::hasColumn('contact_messages', 'pipeline_stage')) {
            Schema::table('contact_messages', function (Blueprint $table): void {
                $table->string('pipeline_stage', 24)->default('new_lead')->after('status')->index();
            });
        }

        if (! Schema::hasColumn('contact_messages', 'pipeline_moved_at')) {
            Schema::table('contact_messages', function (Blueprint $table): void {
                $table->timestamp('pipeline_moved_at')->nullable()->after('pipeline_stage')->index();
            });
        }

        DB::table('contact_messages')->where('status', 'in_progress')->update(['pipeline_stage' => 'contacted']);
        DB::table('contact_messages')->where('status', 'resolved')->update(['pipeline_stage' => 'enrolled']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('contact_messages')) {
            return;
        }

        if (Schema::hasColumn('contact_messages', 'pipeline_moved_at')) {
            Schema::table('contact_messages', fn (Blueprint $table) => $table->dropColumn('pipeline_moved_at'));
        }
        if (Schema::hasColumn('contact_messages', 'pipeline_stage')) {
            Schema::table('contact_messages', fn (Blueprint $table) => $table->dropColumn('pipeline_stage'));
        }
    }
};
