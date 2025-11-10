<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llm_providers', function (Blueprint $table) {
            $table->id();
            
            // Provider Info
            $table->string('provider_name'); // e.g., 'openai', 'anthropic', 'groq'
            $table->string('display_name'); // e.g., 'OpenAI GPT'
            $table->text('description')->nullable();
            
            // API Configuration
            $table->text('api_key'); // Encrypted
            $table->string('api_url')->nullable(); // Base URL for API
            
            // Available Models (JSON array)
            $table->json('available_models'); // ['gpt-4o', 'gpt-4o-mini', 'gpt-3.5-turbo']
            $table->string('default_model')->nullable();
            
            // Pricing (optional, for future billing)
            $table->decimal('price_per_1k_tokens', 10, 6)->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->text('test_error')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llm_providers');
    }
};