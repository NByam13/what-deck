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
            // Get existing columns to avoid duplicates
            $existingColumns = Schema::getColumnListing('cards');
            
            // Helper function to check if column exists
            $hasColumn = function($column) use ($existingColumns) {
                return in_array($column, $existingColumns);
            };

            // Add Scryfall Core Identifiers (these are essential for imports)
            if (!$hasColumn('scryfall_id')) {
                $table->uuid('scryfall_id')->nullable()->unique()->after('id');
            }
            
            if (!$hasColumn('oracle_id')) {
                $table->uuid('oracle_id')->nullable()->index()->after('scryfall_id');
            }
            
            if (!$hasColumn('multiverse_ids')) {
                $table->json('multiverse_ids')->nullable()->after('oracle_id');
            }

            // Platform-specific IDs for card identification
            if (!$hasColumn('mtgo_id')) {
                $table->integer('mtgo_id')->nullable()->index()->after('multiverse_ids');
            }
            
            if (!$hasColumn('mtgo_foil_id')) {
                $table->integer('mtgo_foil_id')->nullable()->after('mtgo_id');
            }
            
            if (!$hasColumn('arena_id')) {
                $table->integer('arena_id')->nullable()->index()->after('mtgo_foil_id');
            }
            
            if (!$hasColumn('tcgplayer_id')) {
                $table->integer('tcgplayer_id')->nullable()->index()->after('arena_id');
            }
            
            if (!$hasColumn('tcgplayer_etched_id')) {
                $table->integer('tcgplayer_etched_id')->nullable()->after('tcgplayer_id');
            }
            
            if (!$hasColumn('cardmarket_id')) {
                $table->integer('cardmarket_id')->nullable()->index()->after('tcgplayer_etched_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            // Get existing columns to check what exists
            $existingColumns = Schema::getColumnListing('cards');
            
            // Columns to remove
            $columnsToRemove = [
                'scryfall_id',
                'oracle_id', 
                'multiverse_ids',
                'mtgo_id',
                'mtgo_foil_id',
                'arena_id',
                'tcgplayer_id',
                'tcgplayer_etched_id',
                'cardmarket_id'
            ];
            
            // Only drop columns that exist
            $columnsToDropArray = array_intersect($columnsToRemove, $existingColumns);
            
            if (!empty($columnsToDropArray)) {
                $table->dropColumn($columnsToDropArray);
            }
        });
    }
};
