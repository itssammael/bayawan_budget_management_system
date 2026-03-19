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
        Schema::create('fund_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('budget_years', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();
            $table->timestamps();
        });

        Schema::create('budget_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('appropriations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fund_source_id')->constrained();
            $table->foreignId('budget_year_id')->constrained();
            $table->foreignId('budget_category_id')->constrained();
            $table->string('account_code')->nullable();
            $table->text('ppa_description');
            $table->decimal('appropriated_amount', 15, 2)->default(0);
            $table->decimal('allotment', 15, 2)->default(0);
            $table->decimal('obligation', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->virtualAs('allotment - obligation');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('procurement_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appropriation_id')->constrained();
            $table->string('transaction_no')->nullable();
            $table->text('item_description');
            $table->string('ppmp_no')->nullable();
            $table->string('pr_no')->nullable();
            $table->string('po_no')->nullable();
            $table->string('status')->nullable();
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->decimal('actual_cost', 15, 2)->default(0);
            $table->date('started_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('aip_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fund_source_id')->constrained();
            $table->string('aip_reference_code')->nullable();
            $table->text('ppa_description');
            $table->string('implementing_office')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('expected_outputs')->nullable();
            $table->decimal('amount_ps', 15, 2)->default(0);
            $table->decimal('amount_mooe', 15, 2)->default(0);
            $table->decimal('amount_fe', 15, 2)->default(0);
            $table->decimal('amount_co', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aip_items');
        Schema::dropIfExists('procurement_transactions');
        Schema::dropIfExists('appropriations');
        Schema::dropIfExists('budget_categories');
        Schema::dropIfExists('budget_years');
        Schema::dropIfExists('fund_sources');
    }
};
