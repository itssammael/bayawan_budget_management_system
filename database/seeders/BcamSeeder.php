<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BcamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fund Sources
        $gf = \App\Models\FundSource::firstOrCreate(['name' => 'General Fund'], ['description' => 'Regular annual budget of the city.']);
        $tf = \App\Models\FundSource::firstOrCreate(['name' => 'Trust Fund'], ['description' => 'Funds held in trust for specific purposes.']);
        $ldrr = \App\Models\FundSource::firstOrCreate(['name' => 'LDRRM Fund'], ['description' => 'Local Disaster Risk Reduction and Management Fund.']);

        // Budget Years
        $y2023 = \App\Models\BudgetYear::firstOrCreate(['year' => 2023]);
        $y2024 = \App\Models\BudgetYear::firstOrCreate(['year' => 2024]);

        // Budget Categories
        $categories = [
            'Travelling Expenses',
            'Training Expenses',
            'Office Supplies Expenses',
            'Food Supplies Expenses',
            'Drugs and Medicines Expenses',
            'Medical Dental & Laboratory Supplies Expenses',
            'Fuel Oil and Lubricants Expenses',
            'Other Supplies and Materials Expenses',
            'Consultancy Services',
            'Repair & Maintenance - Machinery & Equipment',
            'Repair & Maintenance - Transportation Equipment',
            'Insurance Expenses',
            'Printing and Publication Expenses',
            'Rent Expenses',
            'Quick Response Fund (QRF)',
            'FLOOD CONTROL SYSTEMS',
            'DISASTER RESPONSE & RESCUE EQUIPMENT',
            'MACHINERY',
            'OTHER MACHINERY EQUIPMENT',
            'OTHER TRANSPORTATION EQUIPMENT',
            'OTHER PROPERTY PLANT & EQUIPMENT',
        ];

        foreach ($categories as $categoryName) {
            \App\Models\BudgetCategory::updateOrCreate(['name' => $categoryName]);
        }

        $mooe = \App\Models\BudgetCategory::where('name', 'Office Supplies Expenses')->first();
        $co = \App\Models\BudgetCategory::where('name', 'MACHINERY')->first();
        $qrf = \App\Models\BudgetCategory::where('name', 'Quick Response Fund (QRF)')->first();

        // Sample Appropriations for 2024
        \App\Models\Appropriation::updateOrCreate(
            ['ppa_description' => 'Office Supplies Expenses', 'budget_year_id' => $y2024->id],
            [
                'fund_source_id' => $ldrr->id,
                'budget_category_id' => $mooe->id,
                'account_code' => '5-02-03-010',
                'appropriated_amount' => 500000,
                'allotment' => 500000,
                'obligation' => 125000,
                'remarks' => 'Implemented',
            ]
        );

        \App\Models\Appropriation::updateOrCreate(
            ['ppa_description' => 'Motor Vehicles', 'budget_year_id' => $y2024->id],
            [
                'fund_source_id' => $ldrr->id,
                'budget_category_id' => $co->id,
                'account_code' => '1-07-05-020',
                'appropriated_amount' => 2500000,
                'allotment' => 2500000,
                'obligation' => 2500000,
                'remarks' => 'Completed',
            ]
        );

        \App\Models\Appropriation::updateOrCreate(
            ['ppa_description' => 'Relief Operations (QRF)', 'budget_year_id' => $y2024->id],
            [
                'fund_source_id' => $ldrr->id,
                'budget_category_id' => $qrf->id,
                'account_code' => '5-02-99-999',
                'appropriated_amount' => 10000000,
                'allotment' => 5000000,
                'obligation' => 2000000,
                'remarks' => 'Ongoing',
            ]
        );

        // Sample Procurement for Capital Outlay
        $vehicleApp = \App\Models\Appropriation::where('ppa_description', 'Motor Vehicles')
            ->where('budget_year_id', $y2024->id)
            ->first();

        \App\Models\ProcurementTransaction::updateOrCreate(
            ['appropriation_id' => $vehicleApp->id, 'transaction_no' => '2024-VEH-001'],
            [
                'item_description' => '4x4 Rescue Vehicle',
                'ppmp_no' => 'PPMP-2024-05',
                'pr_no' => 'PR-24-0012',
                'po_no' => 'PO-24-0045',
                'status' => 'Delivered',
                'estimated_cost' => 2500000,
                'actual_cost' => 2480000,
                'started_at' => '2024-01-15',
                'completed_at' => '2024-03-10',
            ]
        );
    }
}
