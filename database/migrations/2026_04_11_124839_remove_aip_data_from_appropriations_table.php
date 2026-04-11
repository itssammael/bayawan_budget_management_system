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
            $table->dropColumn('balance');
            $table->dropForeign(['fund_source_id']);
            $table->dropForeign(['ppsa_id']);
            $table->dropColumn(['fund_source_id', 'appropriation_type', 'ppsa_id', 'ppa_description', 'appropriated_amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appropriations', function (Blueprint $table) {
            $table->foreignId('fund_source_id')->constrained();
            $table->foreignId('ppsa_id')->constrained();
            $table->string('appropriation_type')->nullable();
            $table->text('ppa_description')->nullable();
            $table->decimal('appropriated_amount', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->virtualAs('appropriated_amount - obligation')->after('obligation');
        });
    }
};
