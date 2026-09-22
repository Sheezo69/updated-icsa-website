<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contact_messages')) {
            return;
        }

        if (! Schema::hasColumn('contact_messages', 'analytics_visitor_hash')) {
            Schema::table('contact_messages', function (Blueprint $table): void {
                $table->char('analytics_visitor_hash', 64)->nullable()->index()->after('form_type');
            });
        }
        if (! Schema::hasColumn('contact_messages', 'lead_score')) {
            Schema::table('contact_messages', function (Blueprint $table): void {
                $table->unsignedTinyInteger('lead_score')->default(0)->index()->after('analytics_visitor_hash');
            });
        }
    }

    public function down(): void
    {
        // Intelligence data is intentionally preserved during rollback.
    }
};
