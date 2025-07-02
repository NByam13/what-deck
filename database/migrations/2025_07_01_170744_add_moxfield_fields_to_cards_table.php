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
            $table->string('edition', 10)->nullable()->after('subtype');
            $table->string('collector_number', 20)->nullable()->after('edition');
            $table->index(['edition', 'collector_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropIndex(['edition', 'collector_number']);
            $table->dropColumn(['edition', 'collector_number']);
        });
    }
};
