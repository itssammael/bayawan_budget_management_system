<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BudgetYear;
use App\Models\FundSource;
use App\Models\Ppsa;
use App\Models\AipItem;
use App\Models\Appropriation;
use App\Models\ProcurementTransaction;
use App\Models\Department;

class ProcurementMonitoringSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get or create Budget Year 2026
        $year2026 = BudgetYear::firstOrCreate(
            ['year' => 2026],
            ['is_current' => true]
        );

        // Ensure 2026 is current and others are not
        BudgetYear::where('id', '!=', $year2026->id)->update(['is_current' => false]);
        $year2026->update(['is_current' => true]);

        // 2. Find LDRRM Fund source and CDRRMO department
        $ldrrmFund = FundSource::where('name', 'LDRRM Fund')->first() ?? FundSource::firstOrCreate(
            ['name' => 'LDRRM Fund'],
            ['description' => 'Local Disaster Risk Reduction and Management Fund.']
        );

        $cdrrmoDept = Department::where('shortname', 'cdrrmo')->first() ?? Department::where('name', 'like', '%Disaster%')->first();
        $cdrrmoDeptId = $cdrrmoDept ? $cdrrmoDept->id : 3; // Fallback to 3 if not found

        // 3. Load the JSON data
        $jsonPath = database_path('seeders/procurement_data.json');
        if (!file_exists($jsonPath)) {
            $this->command->error("procurement_data.json not found!");
            return;
        }

        $items = json_decode(file_get_contents($jsonPath), true);
        $this->command->info("Seeding " . count($items) . " procurement transactions for 2026...");

        foreach ($items as $index => $item) {
            $desc = $item['ppa_description'];
            $amount = $item['amount'];
            $classification = $item['classification'];
            $prNo = $item['pr_no'];

            // Clean description (e.g. remove newlines)
            $cleanDesc = trim(preg_replace('/\s+/', ' ', $desc));

            // Determine PPSA ID based on description keywords
            $ppsaId = $this->getPpsaId($cleanDesc);

            // Determine classification ID
            $classificationId = ($classification === 'MOOE') ? 1 : 2;

            // Generate a unique reference code
            $refCode = sprintf("3000-2-03-010-%02d", $index + 1);

            // Find or create AIP Item
            $aipItem = AipItem::where('budget_year_id', $year2026->id)
                ->where('ppa_description', $cleanDesc)
                ->first();

            if (!$aipItem) {
                // Try finding with similar ppa_description to avoid duplicates
                $aipItem = AipItem::where('budget_year_id', $year2026->id)
                    ->where('ppa_description', 'like', substr($cleanDesc, 0, 30) . '%')
                    ->first();
            }

            if (!$aipItem) {
                $aipItem = AipItem::create([
                    'budget_year_id' => $year2026->id,
                    'budget_classification_id' => $classificationId,
                    'fund_source_id' => $ldrrmFund->id,
                    'ppsa_id' => $ppsaId,
                    'aip_reference_code' => $refCode,
                    'ppa_description' => $cleanDesc,
                    'department_id' => $cdrrmoDeptId,
                    'start_date' => '2026-01-01',
                    'end_date' => '2026-12-31',
                    'amount' => $amount,
                    'expected_outputs' => 'Target output accomplished',
                ]);
            }

            // Find or create Appropriation
            $appropriation = Appropriation::where('aip_item_id', $aipItem->id)->first();

            if (!$appropriation) {
                // Generate simple account code based on index
                $accountCode = ($classificationId === 1) 
                    ? sprintf("5-02-03-%03d", $index + 1)
                    : sprintf("1-07-05-%03d", $index + 1);

                $appropriation = Appropriation::create([
                    'aip_item_id' => $aipItem->id,
                    'budget_year_id' => $year2026->id,
                    'account_code' => $accountCode,
                    'allotment' => $amount,
                    'obligation' => 0,
                    'remarks' => $prNo ? 'Ongoing' : 'Planned',
                    'department_id' => $cdrrmoDeptId,
                ]);
            }

            // Find or create Procurement Transaction
            $transactionNo = sprintf("2026-DRRM-%03d", $index + 1);
            $ppmpNo = sprintf("PPMP-2026-%03d", $index + 1);

            ProcurementTransaction::updateOrCreate(
                [
                    'appropriation_id' => $appropriation->id,
                    'item_description' => $cleanDesc,
                ],
                [
                    'transaction_no' => $transactionNo,
                    'ppmp_no' => $ppmpNo,
                    'pr_no' => $prNo,
                    'po_no' => null,
                    'status' => $prNo ? 'On Process' : 'Planned',
                    'estimated_cost' => $amount,
                    'actual_cost' => 0,
                    'started_at' => $prNo ? '2026-01-15' : null,
                    'completed_at' => null,
                    'department_id' => $cdrrmoDeptId,
                ]
            );
        }

        $this->command->info("Procurement data successfully seeded!");
    }

    private function getPpsaId(string $desc): int
    {
        $descLower = strtolower($desc);

        if (str_contains($descLower, 'travelling')) return 1;
        if (str_contains($descLower, 'training')) return 2;
        if (str_contains($descLower, 'office supplies')) return 3;
        if (str_contains($descLower, 'food')) return 4;
        if (str_contains($descLower, 'drugs') || str_contains($descLower, 'medicine')) return 5;
        if (str_contains($descLower, 'medical') || str_contains($descLower, 'laboratory')) return 6;
        if (str_contains($descLower, 'fuel') || str_contains($descLower, 'oil')) return 7;
        if (str_contains($descLower, 'other supplies')) return 8;
        if (str_contains($descLower, 'repair & maintenance') && str_contains($descLower, 'machinery')) return 10;
        if (str_contains($descLower, 'repair & maintenance') && str_contains($descLower, 'transportation')) return 11;
        if (str_contains($descLower, 'repair & maintenance') && str_contains($descLower, 'building')) return 22;
        if (str_contains($descLower, 'insurance')) return 12;
        if (str_contains($descLower, 'printing') || str_contains($descLower, 'publication')) return 13;
        if (str_contains($descLower, 'rent')) return 14;
        
        if (
            str_contains($descLower, 'inflatable') || 
            str_contains($descLower, 'rescue') || 
            str_contains($descLower, 'boat') || 
            str_contains($descLower, 'vehicle') || 
            str_contains($descLower, 'dog pound') || 
            str_contains($descLower, 'chainsaw') || 
            str_contains($descLower, 'generator') || 
            str_contains($descLower, 'tent') || 
            str_contains($descLower, 'air cooler')
        ) return 17; // Disaster Response & Rescue Equipment
        
        if (
            str_contains($descLower, 'computer') || 
            str_contains($descLower, 'laptop') || 
            str_contains($descLower, 'printer')
        ) return 27; // ICT Equipment
        
        if (
            str_contains($descLower, 'drone') || 
            str_contains($descLower, 'camera') || 
            str_contains($descLower, 'dslr')
        ) return 28; // Technical & Scientific Equipment
        
        if (
            str_contains($descLower, 'backhoe') || 
            str_contains($descLower, 'payloader')
        ) return 29; // Construction & Heavy Equipment
        
        if (
            str_contains($descLower, 'livestock evac') || 
            str_contains($descLower, 'staging area') || 
            str_contains($descLower, 'canal') || 
            str_contains($descLower, 'irrigation') || 
            str_contains($descLower, 'cns')
        ) return 26; // Other Structures
        
        if (
            str_contains($descLower, 'flood') || 
            str_contains($descLower, 'bridge') || 
            str_contains($descLower, 'footbridge') || 
            str_contains($descLower, 'riprap') || 
            str_contains($descLower, 'slope') || 
            str_contains($descLower, 'tan-ayan') || 
            str_contains($descLower, 'terong') || 
            str_contains($descLower, 'tabuan')
        ) return 16; // FLOOD CONTROL SYSTEMS
        
        if (str_contains($descLower, 'quick response') || str_contains($descLower, 'qrf')) return 15;
        if (str_contains($descLower, 'unprogrammed')) return 30;

        return 8; // Default to Other Supplies
    }
}
