<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Menambahkan sistem Tags/Groups sebagai filter tambahan
     * tanpa mengubah struktur ContactList yang sudah ada
     */
    public function up(): void
    {
        // Tabel untuk Contact Tags/Groups
        Schema::create('contact_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_list_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('color')->default('#6c757d'); // Bootstrap color
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Prevent duplicate tag names in same contact list
            $table->unique(['contact_list_id', 'name']);
        });

        // Pivot table untuk many-to-many relationship
        Schema::create('contact_contact_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Prevent duplicate entries
            $table->unique(['contact_id', 'contact_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_contact_tag');
        Schema::dropIfExists('contact_tags');
    }
};