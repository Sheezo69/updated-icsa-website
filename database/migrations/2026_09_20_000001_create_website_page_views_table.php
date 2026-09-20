<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('website_page_views')) {
            return;
        }

        Schema::create('website_page_views', function (Blueprint $table): void {
            $table->id();
            $table->char('visitor_hash', 64);
            $table->string('page_path', 255);
            $table->string('page_title', 190)->nullable();
            $table->string('route_name', 120)->nullable();
            $table->string('page_type', 80)->nullable();
            $table->string('referrer_host', 190)->nullable();
            $table->string('utm_source', 120)->nullable();
            $table->string('utm_medium', 120)->nullable();
            $table->string('utm_campaign', 160)->nullable();
            $table->string('device_type', 30);
            $table->string('browser', 60);
            $table->string('operating_system', 60);
            $table->char('country_code', 2)->nullable();
            $table->boolean('is_signed_in')->default(false);
            $table->timestamp('visited_at')->useCurrent();

            $table->index('visited_at');
            $table->index(['visitor_hash', 'visited_at']);
            $table->index(['page_path', 'visited_at']);
            $table->index(['referrer_host', 'visited_at']);
            $table->index(['utm_campaign', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_page_views');
    }
};
