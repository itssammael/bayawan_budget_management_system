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
        Schema::table('appropriations', function (Blueprint $table) {
            $table->enum('appropriation_type', ['MOOE', 'Capital Outlay'])->nullable()->after('ppa_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appropriations', function (Blueprint $table) {
            $table->dropColumn('appropriation_type');
        });
    }
};
