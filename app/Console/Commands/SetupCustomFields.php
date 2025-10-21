<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SetupCustomFields extends Command
{
    protected $signature = 'setup:custom-fields';
    protected $description = 'Setup custom fields for contacts';

    public function handle()
    {
        $this->info('🚀 Setting up Custom Fields...');
        $this->newLine();

        // Check if contacts table exists
        if (!Schema::hasTable('contacts')) {
            $this->error('❌ Contacts table does not exist!');
            $this->info('💡 Run: php artisan migrate');
            return 1;
        }

        // Check if custom_fields column exists
        if (Schema::hasColumn('contacts', 'custom_fields')) {
            $this->info('✅ Custom fields column already exists!');
        } else {
            $this->warn('⚠️  Adding custom_fields column...');
            
            try {
                Schema::table('contacts', function ($table) {
                    $table->json('custom_fields')->nullable()->after('notes');
                });
                
                $this->info('✅ Custom fields column added successfully!');
            } catch (\Exception $e) {
                $this->error('❌ Failed to add column: ' . $e->getMessage());
                return 1;
            }
        }

        $this->newLine();
        $this->info('📋 Custom Fields Setup Complete!');
        $this->newLine();
        
        $this->line('📖 Usage Guide:');
        $this->line('  1. Import CSV with custom columns:');
        $this->line('     name,phone,email,discount,city,product');
        $this->line('     John,0812...,john@...,10,Jakarta,Laptop');
        $this->newLine();
        
        $this->line('  2. Use in templates:');
        $this->line('     Hi {name}! Special {discount}% off in {city}!');
        $this->newLine();
        
        $this->line('  3. Access programmatically:');
        $this->line('     $contact->getCustomField(\'discount\')');
        $this->line('     $contact->setCustomField(\'discount\', \'15\')');
        $this->newLine();
        
        $this->info('💡 Check CUSTOM_FIELDS_GUIDE.md for complete documentation');
        
        return 0;
    }
}