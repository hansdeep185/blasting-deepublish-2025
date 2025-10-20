<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_name')->unique();
            $table->string('phone_number')->nullable();
            $table->enum('status', ['pending', 'connected', 'disconnected', 'failed'])->default('pending');
            $table->text('qr_code')->nullable();
            $table->string('waha_session_id')->nullable();
            $table->boolean('ai_agent_active')->default(false);
            $table->integer('rate_limit_delay')->default(3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};