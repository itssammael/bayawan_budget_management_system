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
            $table->foreignId('ppsa_id')->nullable()->after('fund_source_id')->constrained('ppsas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aip_items', function (Blueprint $table) {
            $table->dropForeign(['ppsa_id']);
            $table->dropColumn('ppsa_id');
        });
    }
};
