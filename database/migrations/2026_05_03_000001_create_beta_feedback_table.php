<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beta_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('environment', 50)->default('production');
            $table->string('page_url')->nullable();
            $table->string('issue_link')->nullable();
            $table->text('message');
            $table->string('screenshot_url')->nullable();
            $table->string('screenshot_public_id')->nullable();
            $table->string('status', 30)->default('new');
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('environment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beta_feedback');
    }
};
