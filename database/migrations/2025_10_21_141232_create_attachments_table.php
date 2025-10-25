<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // File info
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->bigInteger('file_size'); // in bytes
            $table->string('file_type'); // image, video, document, audio
            
            // Storage info
            $table->string('storage_driver')->default('s3'); // s3, local
            $table->string('storage_path');
            $table->string('storage_url');
            
            // Metadata
            $table->integer('width')->nullable(); // for images/videos
            $table->integer('height')->nullable(); // for images/videos
            $table->integer('duration')->nullable(); // for videos/audios (seconds)
            
            // Usage tracking
            $table->integer('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('file_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};