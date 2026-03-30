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
        Schema::rename('budget_categories', 'ppsas');

        Schema::table('appropriations', function (Blueprint $table) {
            $table->dropForeign(['budget_category_id']);
            $table->renameColumn('budget_category_id', 'ppsa_id');
            $table->foreign('ppsa_id')->references('id')->on('ppsas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appropriations', function (Blueprint $table) {
            $table->dropForeign(['ppsa_id']);
            $table->renameColumn('ppsa_id', 'budget_category_id');
            $table->foreign('budget_category_id')->references('id')->on('budget_categories')->onDelete('cascade');
        });

        Schema::rename('ppsas', 'budget_categories');
    }
};
