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
        Schema::table('aip_items', function (Blueprint $table) {
            $table->foreignId('budget_year_id')->nullable()->after('id')->constrained();
            $table->foreignId('budget_category_id')->nullable()->after('budget_year_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aip_items', function (Blueprint $table) {
            $table->dropForeign(['budget_year_id']);
            $table->dropForeign(['budget_category_id']);
            $table->dropColumn(['budget_year_id', 'budget_category_id']);
        });
    }
};
