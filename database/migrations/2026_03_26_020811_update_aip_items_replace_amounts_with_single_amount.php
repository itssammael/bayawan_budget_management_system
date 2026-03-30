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
            $table->dropColumn(['amount_ps', 'amount_mooe', 'amount_fe', 'amount_co']);
            $table->decimal('amount', 15, 2)->default(0.00)->after('expected_outputs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aip_items', function (Blueprint $table) {
            $table->dropColumn('amount');
            $table->decimal('amount_ps', 15, 2)->default(0.00);
            $table->decimal('amount_mooe', 15, 2)->default(0.00);
            $table->decimal('amount_fe', 15, 2)->default(0.00);
            $table->decimal('amount_co', 15, 2)->default(0.00);
        });
    }
};
