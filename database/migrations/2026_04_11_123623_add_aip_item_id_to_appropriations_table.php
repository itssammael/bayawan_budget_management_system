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
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \Illuminate\Support\Facades\DB::table('procurement_transactions')->truncate();
        \Illuminate\Support\Facades\DB::table('appropriations')->truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Schema::table('appropriations', function (Blueprint $table) {
            $table->foreignId('aip_item_id')->constrained('aip_items')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appropriations', function (Blueprint $table) {
            $table->dropForeign(['aip_item_id']);
            $table->dropColumn('aip_item_id');
        });
    }
};
