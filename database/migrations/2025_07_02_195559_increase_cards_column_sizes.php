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
        Schema::table('cards', function (Blueprint $table) {
            // Get existing columns to check what exists
            $existingColumns = Schema::getColumnListing('cards');
            
            // Helper function to check if column exists
            $hasColumn = function($column) use ($existingColumns) {
                return in_array($column, $existingColumns);
            };
            
            // Fix original table columns that are too small
            if ($hasColumn('edition')) {
                $table->string('edition', 100)->nullable()->change(); // Increased from likely 50 to 100
            }
            
            if ($hasColumn('title')) {
                $table->string('title', 200)->nullable()->change(); // Ensure adequate space for card names
            }
            
            if ($hasColumn('cost')) {
                $table->string('cost', 50)->nullable()->change(); // Ensure adequate space for mana costs
            }
            
            if ($hasColumn('type')) {
                $table->string('type', 100)->nullable()->change(); // Ensure adequate space for types
            }
            
            if ($hasColumn('subtype')) {
                $table->string('subtype', 200)->nullable()->change(); // Ensure adequate space for subtypes
            }
            
            if ($hasColumn('collector_number')) {
                $table->string('collector_number', 20)->nullable()->change(); // Ensure adequate space
            }
            
            // Fix Scryfall-specific columns that might be too small
            if ($hasColumn('mana_cost')) {
                $table->string('mana_cost', 100)->nullable()->change(); // For complex mana costs
            }
            
            if ($hasColumn('type_line')) {
                $table->string('type_line', 200)->nullable()->change(); // For long type lines
            }
            
            if ($hasColumn('set')) {
                $table->string('set', 20)->nullable()->change(); // Increased from 10 to 20 for set codes
            }
            
            if ($hasColumn('set_name')) {
                $table->string('set_name', 100)->nullable()->change(); // Ensure adequate space for set names
            }
            
            if ($hasColumn('set_type')) {
                $table->string('set_type', 50)->nullable()->change(); // Keep current size
            }
            
            if ($hasColumn('rarity')) {
                $table->string('rarity', 30)->nullable()->change(); // Increased from 20 to 30
            }
            
            if ($hasColumn('layout')) {
                $table->string('layout', 50)->nullable()->change(); // Increased from 30 to 50
            }
            
            if ($hasColumn('image_status')) {
                $table->string('image_status', 30)->nullable()->change(); // Increased from 20 to 30
            }
            
            if ($hasColumn('border_color')) {
                $table->string('border_color', 30)->nullable()->change(); // Increased from 20 to 30
            }
            
            if ($hasColumn('frame')) {
                $table->string('frame', 20)->nullable()->change(); // Increased from 10 to 20
            }
            
            if ($hasColumn('frame_effects')) {
                $table->string('frame_effects', 200)->nullable()->change(); // Ensure adequate space
            }
            
            if ($hasColumn('security_stamp')) {
                $table->string('security_stamp', 50)->nullable()->change(); // Increased from 20 to 50
            }
            
            if ($hasColumn('watermark')) {
                $table->string('watermark', 100)->nullable()->change(); // Increased from 50 to 100
            }
            
            if ($hasColumn('artist')) {
                $table->string('artist', 200)->nullable()->change(); // Ensure adequate space for artist names
            }
            
            // Fix stat columns that might need to handle special values
            if ($hasColumn('power')) {
                $table->string('power', 20)->nullable()->change(); // Handle special values like "*", "X", etc.
            }
            
            if ($hasColumn('toughness')) {
                $table->string('toughness', 20)->nullable()->change(); // Handle special values like "*", "X", etc.
            }
            
            if ($hasColumn('loyalty')) {
                $table->string('loyalty', 20)->nullable()->change(); // Handle special values
            }
            
            if ($hasColumn('defense')) {
                $table->string('defense', 20)->nullable()->change(); // Handle special values
            }
            
            if ($hasColumn('hand_modifier')) {
                $table->string('hand_modifier', 20)->nullable()->change(); // Handle special values
            }
            
            if ($hasColumn('life_modifier')) {
                $table->string('life_modifier', 20)->nullable()->change(); // Handle special values
            }
            
            if ($hasColumn('lang')) {
                $table->string('lang', 10)->default('en')->change(); // Increased from 5 to 10
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            // Get existing columns
            $existingColumns = Schema::getColumnListing('cards');
            
            // Helper function to check if column exists
            $hasColumn = function($column) use ($existingColumns) {
                return in_array($column, $existingColumns);
            };
            
            // Revert to original sizes (if known) - be careful with this in production
            // Note: These are approximate original sizes - adjust based on your original schema
            
            if ($hasColumn('edition')) {
                $table->string('edition', 50)->nullable()->change();
            }
            
            if ($hasColumn('title')) {
                $table->string('title', 100)->nullable()->change();
            }
            
            if ($hasColumn('cost')) {
                $table->string('cost', 20)->nullable()->change();
            }
            
            if ($hasColumn('type')) {
                $table->string('type', 50)->nullable()->change();
            }
            
            if ($hasColumn('subtype')) {
                $table->string('subtype', 100)->nullable()->change();
            }
            
            if ($hasColumn('collector_number')) {
                $table->string('collector_number', 10)->nullable()->change();
            }
            
            if ($hasColumn('mana_cost')) {
                $table->string('mana_cost', 50)->nullable()->change();
            }
            
            if ($hasColumn('type_line')) {
                $table->string('type_line', 100)->nullable()->change();
            }
            
            if ($hasColumn('set')) {
                $table->string('set', 10)->nullable()->change();
            }
            
            if ($hasColumn('set_name')) {
                $table->string('set_name', 50)->nullable()->change();
            }
            
            if ($hasColumn('rarity')) {
                $table->string('rarity', 20)->nullable()->change();
            }
            
            if ($hasColumn('layout')) {
                $table->string('layout', 30)->nullable()->change();
            }
            
            if ($hasColumn('image_status')) {
                $table->string('image_status', 20)->nullable()->change();
            }
            
            if ($hasColumn('border_color')) {
                $table->string('border_color', 20)->nullable()->change();
            }
            
            if ($hasColumn('frame')) {
                $table->string('frame', 10)->nullable()->change();
            }
            
            if ($hasColumn('frame_effects')) {
                $table->string('frame_effects', 100)->nullable()->change();
            }
            
            if ($hasColumn('security_stamp')) {
                $table->string('security_stamp', 20)->nullable()->change();
            }
            
            if ($hasColumn('watermark')) {
                $table->string('watermark', 50)->nullable()->change();
            }
            
            if ($hasColumn('artist')) {
                $table->string('artist', 100)->nullable()->change();
            }
            
            if ($hasColumn('power')) {
                $table->string('power', 10)->nullable()->change();
            }
            
            if ($hasColumn('toughness')) {
                $table->string('toughness', 10)->nullable()->change();
            }
            
            if ($hasColumn('loyalty')) {
                $table->string('loyalty', 10)->nullable()->change();
            }
            
            if ($hasColumn('defense')) {
                $table->string('defense', 10)->nullable()->change();
            }
            
            if ($hasColumn('hand_modifier')) {
                $table->string('hand_modifier', 10)->nullable()->change();
            }
            
            if ($hasColumn('life_modifier')) {
                $table->string('life_modifier', 10)->nullable()->change();
            }
            
            if ($hasColumn('lang')) {
                $table->string('lang', 5)->default('en')->change();
            }
        });
    }
};
