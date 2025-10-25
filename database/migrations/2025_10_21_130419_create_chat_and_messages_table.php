<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chats/Conversations table
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->string('contact_phone'); // WhatsApp number
            $table->string('contact_name')->nullable();
            $table->text('last_message')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->integer('unread_count')->default(0);
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
            
            $table->index('account_id');
            $table->index('contact_phone');
            $table->index('last_message_at');
        });

        // Messages table
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained('chats')->onDelete('cascade');
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            
            $table->enum('direction', ['incoming', 'outgoing']); // incoming from contact, outgoing to contact
            $table->string('from_phone');
            $table->string('to_phone');
            $table->text('message_content');
            
            // Media
            $table->string('media_type')->nullable(); // image, video, document, audio
            $table->string('media_url')->nullable();
            $table->string('media_caption')->nullable();
            
            // Status
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            
            // WAHA
            $table->string('waha_message_id')->nullable();
            $table->json('waha_response')->nullable();
            
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index('chat_id');
            $table->index('account_id');
            $table->index('direction');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chats');
    }
};