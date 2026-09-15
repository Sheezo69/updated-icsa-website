<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'avatar_path' => fn (Blueprint $table) => $table->string('avatar_path')->nullable()->after('email'),
            'notify_email' => fn (Blueprint $table) => $table->boolean('notify_email')->default(true)->after('avatar_path'),
            'notify_inquiries' => fn (Blueprint $table) => $table->boolean('notify_inquiries')->default(true)->after('notify_email'),
            'notify_messages' => fn (Blueprint $table) => $table->boolean('notify_messages')->default(true)->after('notify_inquiries'),
            'timezone' => fn (Blueprint $table) => $table->string('timezone', 64)->default('Asia/Kuwait')->after('notify_messages'),
            'language' => fn (Blueprint $table) => $table->string('language', 8)->default('en')->after('timezone'),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('admins', $column)) {
                Schema::table('admins', $definition);
            }
        }
    }

    public function down(): void
    {
        $columns = ['avatar_path', 'notify_email', 'notify_inquiries', 'notify_messages', 'timezone', 'language'];
        $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('admins', $column)));

        if ($existing !== []) {
            Schema::table('admins', fn (Blueprint $table) => $table->dropColumn($existing));
        }
    }
};
