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
        if (!Schema::hasColumn('aip_implementing_department', 'id')) {
            Schema::table('aip_implementing_department', function (Blueprint $table) {
                $table->id()->first();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('aip_implementing_department', 'id')) {
            Schema::table('aip_implementing_department', function (Blueprint $table) {
                $table->dropColumn('id');
            });
        }
    }
};
