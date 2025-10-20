<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->string('chat_id');
            $table->string('from_number');
            $table->string('to_number')->nullable();
            $table->enum('direction', ['inbound', 'outbound']);
            $table->text('message_content');
            $table->enum('message_type', ['text', 'image', 'document', 'audio', 'video'])->default('text');
            $table->string('media_url')->nullable();
            $table->string('waha_message_id')->nullable();
            $table->boolean('is_ai_response')->default(false);
            $table->timestamp('message_timestamp');
            $table->timestamps();
            
            $table->index(['account_id', 'chat_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_messages');
    }
};