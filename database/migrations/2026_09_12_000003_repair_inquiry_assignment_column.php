<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('contact_messages', 'assigned_to')) {
            Schema::table('contact_messages', function (Blueprint $table): void {
                $table->unsignedBigInteger('assigned_to')->nullable()->after('updated_by')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('contact_messages', 'assigned_to')) {
            Schema::table('contact_messages', function (Blueprint $table): void {
                $table->dropIndex(['assigned_to']);
                $table->dropColumn('assigned_to');
            });
        }
    }
};
