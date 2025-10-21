<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blast_schedules', function (Blueprint $table) {
            // Add missing columns
            if (!Schema::hasColumn('blast_schedules', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            
            if (!Schema::hasColumn('blast_schedules', 'target_type')) {
                $table->enum('target_type', ['all', 'contact_list', 'contact_group', 'selected_contacts'])
                    ->default('contact_list')
                    ->after('contact_list_id');
            }
            
            if (!Schema::hasColumn('blast_schedules', 'target_ids')) {
                $table->json('target_ids')->nullable()->after('target_type');
            }
            
            if (!Schema::hasColumn('blast_schedules', 'schedule_type')) {
                $table->enum('schedule_type', ['immediate', 'scheduled'])
                    ->default('immediate')
                    ->after('target_ids');
            }
            
            if (!Schema::hasColumn('blast_schedules', 'pending_count')) {
                $table->integer('pending_count')->default(0)->after('failed_count');
            }
            
            // Update status enum to include 'pending'
            DB::statement("ALTER TABLE blast_schedules DROP CONSTRAINT IF EXISTS blast_schedules_status_check");
            DB::statement("ALTER TABLE blast_schedules ADD CONSTRAINT blast_schedules_status_check CHECK (status IN ('draft', 'pending', 'scheduled', 'processing', 'completed', 'failed', 'cancelled'))");
        });
        
        // Rename sent_messages to blast_messages for consistency
        if (Schema::hasTable('sent_messages') && !Schema::hasTable('blast_messages')) {
            Schema::rename('sent_messages', 'blast_messages');
        }
        
        // Add columns to blast_messages if needed
        Schema::table('blast_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('blast_messages', 'recipient_name')) {
                $table->string('recipient_name')->nullable()->after('phone_number');
            }
            
            if (!Schema::hasColumn('blast_messages', 'waha_response')) {
                $table->json('waha_response')->nullable()->after('waha_message_id');
            }
            
            // Add 'queued' status
            DB::statement("ALTER TABLE blast_messages DROP CONSTRAINT IF EXISTS blast_messages_status_check");
            DB::statement("ALTER TABLE blast_messages ADD CONSTRAINT blast_messages_status_check CHECK (status IN ('pending', 'queued', 'sent', 'failed', 'delivered', 'read'))");
        });
    }

    public function down(): void
    {
        Schema::table('blast_schedules', function (Blueprint $table) {
            $table->dropColumn([
                'description', 
                'target_type', 
                'target_ids', 
                'schedule_type',
                'pending_count'
            ]);
        });
        
        Schema::table('blast_messages', function (Blueprint $table) {
            $table->dropColumn(['recipient_name', 'waha_response']);
        });
        
        if (Schema::hasTable('blast_messages')) {
            Schema::rename('blast_messages', 'sent_messages');
        }
    }
};