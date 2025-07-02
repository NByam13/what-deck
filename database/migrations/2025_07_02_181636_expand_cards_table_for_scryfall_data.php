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

            // Core Game Data (scryfall_id, oracle_id, etc. already exist)
            if (!$hasColumn('mana_cost')) $table->string('mana_cost')->nullable()->after('cost');
            if (!$hasColumn('cmc')) $table->decimal('cmc', 5, 2)->nullable()->after('mana_cost');
            if (!$hasColumn('oracle_text')) $table->text('oracle_text')->nullable()->after('description');
            if (!$hasColumn('flavor_text')) $table->text('flavor_text')->nullable()->after('oracle_text');
            if (!$hasColumn('type_line')) $table->string('type_line')->nullable()->after('subtype');
            
            // Add compatibility field 
            if (!$hasColumn('image')) $table->string('image')->nullable()->after('image_url');
            
            // Colors and Identity
            if (!$hasColumn('colors')) $table->json('colors')->nullable()->after('collector_number');
            if (!$hasColumn('color_identity')) $table->json('color_identity')->nullable()->after('colors');
            if (!$hasColumn('color_indicator')) $table->json('color_indicator')->nullable()->after('color_identity');
            if (!$hasColumn('keywords')) $table->json('keywords')->nullable()->after('color_indicator');
            if (!$hasColumn('produced_mana')) $table->json('produced_mana')->nullable()->after('keywords');

            // Stats and Attributes
            if (!$hasColumn('loyalty')) $table->string('loyalty')->nullable()->after('toughness');
            if (!$hasColumn('defense')) $table->string('defense')->nullable()->after('loyalty');
            if (!$hasColumn('hand_modifier')) $table->string('hand_modifier')->nullable()->after('defense');
            if (!$hasColumn('life_modifier')) $table->string('life_modifier')->nullable()->after('hand_modifier');

            // Legalities and Rankings
            if (!$hasColumn('legalities')) $table->json('legalities')->nullable()->after('life_modifier');
            if (!$hasColumn('edhrec_rank')) $table->integer('edhrec_rank')->nullable()->after('legalities');
            if (!$hasColumn('penny_rank')) $table->integer('penny_rank')->nullable()->after('edhrec_rank');

            // Set and Printing Info
            if (!$hasColumn('set')) $table->string('set', 10)->nullable()->after('produced_mana');
            if (!$hasColumn('set_id')) $table->uuid('set_id')->nullable()->after('set');
            if (!$hasColumn('set_name')) $table->string('set_name')->nullable()->after('set_id');
            if (!$hasColumn('set_type')) $table->string('set_type', 50)->nullable()->after('set_name');
            if (!$hasColumn('rarity')) $table->string('rarity', 20)->nullable()->after('set_type');
            if (!$hasColumn('released_at')) $table->date('released_at')->nullable()->after('rarity');
            if (!$hasColumn('lang')) $table->string('lang', 5)->default('en')->after('released_at');

            // Visual and Physical Properties
            if (!$hasColumn('image_uris')) $table->json('image_uris')->nullable()->after('lang');
            if (!$hasColumn('layout')) $table->string('layout', 30)->nullable()->after('image_uris');
            if (!$hasColumn('highres_image')) $table->boolean('highres_image')->default(false)->after('layout');
            if (!$hasColumn('image_status')) $table->string('image_status', 20)->nullable()->after('highres_image');
            if (!$hasColumn('border_color')) $table->string('border_color', 20)->nullable()->after('image_status');
            if (!$hasColumn('frame')) $table->string('frame', 10)->nullable()->after('border_color');
            if (!$hasColumn('frame_effects')) $table->string('frame_effects')->nullable()->after('frame');
            if (!$hasColumn('security_stamp')) $table->string('security_stamp', 20)->nullable()->after('frame_effects');
            if (!$hasColumn('watermark')) $table->string('watermark', 50)->nullable()->after('security_stamp');

            // Artist Information
            if (!$hasColumn('artist')) $table->string('artist')->nullable()->after('watermark');
            if (!$hasColumn('artist_ids')) $table->json('artist_ids')->nullable()->after('artist');
            if (!$hasColumn('illustration_id')) $table->uuid('illustration_id')->nullable()->after('artist_ids');

            // Boolean Flags
            if (!$hasColumn('reserved')) $table->boolean('reserved')->default(false)->after('illustration_id');
            if (!$hasColumn('foil')) $table->boolean('foil')->default(false)->after('reserved');
            if (!$hasColumn('nonfoil')) $table->boolean('nonfoil')->default(true)->after('foil');
            if (!$hasColumn('oversized')) $table->boolean('oversized')->default(false)->after('nonfoil');
            if (!$hasColumn('promo')) $table->boolean('promo')->default(false)->after('oversized');
            if (!$hasColumn('reprint')) $table->boolean('reprint')->default(false)->after('promo');
            if (!$hasColumn('variation')) $table->boolean('variation')->default(false)->after('reprint');
            if (!$hasColumn('digital')) $table->boolean('digital')->default(false)->after('variation');
            if (!$hasColumn('full_art')) $table->boolean('full_art')->default(false)->after('digital');
            if (!$hasColumn('textless')) $table->boolean('textless')->default(false)->after('full_art');
            if (!$hasColumn('booster')) $table->boolean('booster')->default(true)->after('textless');
            if (!$hasColumn('story_spotlight')) $table->boolean('story_spotlight')->default(false)->after('booster');
            if (!$hasColumn('game_changer')) $table->boolean('game_changer')->default(false)->after('story_spotlight');

            // Additional Data
            if (!$hasColumn('finishes')) $table->json('finishes')->nullable()->after('game_changer');
            if (!$hasColumn('games')) $table->json('games')->nullable()->after('finishes');
            if (!$hasColumn('promo_types')) $table->json('promo_types')->nullable()->after('games');
            if (!$hasColumn('prices')) $table->json('prices')->nullable()->after('promo_types');
            if (!$hasColumn('purchase_uris')) $table->json('purchase_uris')->nullable()->after('prices');
            if (!$hasColumn('related_uris')) $table->json('related_uris')->nullable()->after('purchase_uris');
            if (!$hasColumn('variation_of')) $table->uuid('variation_of')->nullable()->after('related_uris');
            if (!$hasColumn('card_back_id')) $table->uuid('card_back_id')->nullable()->after('variation_of');

            // URIs
            if (!$hasColumn('scryfall_uri')) $table->text('scryfall_uri')->nullable()->after('card_back_id');
            if (!$hasColumn('uri')) $table->text('uri')->nullable()->after('scryfall_uri');
            if (!$hasColumn('rulings_uri')) $table->text('rulings_uri')->nullable()->after('uri');
            if (!$hasColumn('prints_search_uri')) $table->text('prints_search_uri')->nullable()->after('rulings_uri');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            // Drop columns that were added (check if they exist first)
            $existingColumns = Schema::getColumnListing('cards'); 
            $columnsToRemove = [
                'mana_cost', 'cmc', 'oracle_text', 'flavor_text', 'type_line', 'image',
                'colors', 'color_identity', 'color_indicator', 'keywords', 'produced_mana',
                'loyalty', 'defense', 'hand_modifier', 'life_modifier', 'legalities',
                'edhrec_rank', 'penny_rank', 'set', 'set_id', 'set_name', 'set_type',
                'rarity', 'released_at', 'lang', 'image_uris', 'layout', 'highres_image',
                'image_status', 'border_color', 'frame', 'frame_effects', 'security_stamp',
                'watermark', 'artist', 'artist_ids', 'illustration_id', 'reserved', 'foil',
                'nonfoil', 'oversized', 'promo', 'reprint', 'variation', 'digital',
                'full_art', 'textless', 'booster', 'story_spotlight', 'game_changer',
                'finishes', 'games', 'promo_types', 'prices', 'purchase_uris',
                'related_uris', 'variation_of', 'card_back_id', 'scryfall_uri', 'uri',
                'rulings_uri', 'prints_search_uri'
            ];
            
            $columnsToDropArray = array_intersect($columnsToRemove, $existingColumns);
            
            if (!empty($columnsToDropArray)) {
                $table->dropColumn($columnsToDropArray);
            }
        });
    }
};
