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
        Schema::table('card_instances', function (Blueprint $table) {
            $table->string('language', 50)->default('English')->after('foil');
            $table->json('tags')->nullable()->after('language');
            $table->decimal('purchase_price', 10, 2)->nullable()->after('tags');
            $table->boolean('alter')->default(false)->after('purchase_price');
            $table->boolean('proxy')->default(false)->after('alter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('card_instances', function (Blueprint $table) {
            $table->dropColumn(['language', 'tags', 'purchase_price', 'alter', 'proxy']);
        });
    }
};
