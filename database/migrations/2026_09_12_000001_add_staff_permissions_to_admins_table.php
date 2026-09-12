<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->boolean('can_manage_courses')->default(false)->after('role');
            $table->boolean('can_manage_media')->default(false)->after('can_manage_courses');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->dropColumn(['can_manage_courses', 'can_manage_media']);
        });
    }
};
