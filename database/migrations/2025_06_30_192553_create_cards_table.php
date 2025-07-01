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
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image_url')->nullable();
            $table->text('description')->nullable();
            $table->string('cost')->nullable(); // Mana cost like "2R" or "1UU"
            $table->string('type'); // Creature, Instant, Sorcery, etc.
            $table->string('subtype')->nullable(); // Human Warrior, Lightning, etc.
            $table->integer('power')->nullable(); // For creatures
            $table->integer('toughness')->nullable(); // For creatures
            $table->timestamps();
            
            // Indexes for common queries
            $table->index('type');
            $table->index('subtype');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
