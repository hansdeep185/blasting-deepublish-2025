<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            // Hapus kolom lama (jika perlu)
            $table->dropColumn('placeholders');
            $table->dropColumn('type');

            // Tambah kolom baru
            $table->enum('category', ['marketing', 'notification', 'reminder', 'greeting', 'other'])->default('other')->after('content');
            $table->json('variables')->nullable()->after('category');
            $table->string('media_type')->nullable()->after('variables');
            $table->text('description')->nullable()->after('media_url');
            $table->boolean('is_active')->default(true)->after('description');
            $table->timestamp('last_used_at')->nullable()->after('usage_count');

            // Tambah index
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            // Kebalikan dari perintah 'up'
            $table->json('placeholders')->nullable();
            $table->enum('type', ['text', 'image', 'document'])->default('text');

            $table->dropColumn(['category', 'variables', 'media_type', 'description', 'is_active', 'last_used_at']);
            $table->dropIndex(['category']);
        });
    }
};
