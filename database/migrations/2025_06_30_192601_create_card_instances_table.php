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
        Schema::create('card_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained()->onDelete('cascade');
            $table->foreignId('collection_id')->constrained()->onDelete('cascade');
            $table->foreignId('deck_id')->nullable()->constrained()->onDelete('set null');
            $table->string('condition')->default('near_mint'); // near_mint, lightly_played, etc.
            $table->boolean('foil')->default(false);
            $table->timestamps();
            
            // Indexes for common queries
            $table->index(['collection_id', 'deck_id']);
            $table->index('deck_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_instances');
    }
};
