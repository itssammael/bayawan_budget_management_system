<?php

namespace App\Http\Controllers;

use App\Models\Appropriation;
use App\Models\BudgetYear;
use App\Models\FundSource;
use App\Models\Ppsa;
use App\Models\BudgetClassification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BudgetController extends Controller
{
    public function dashboard()
    {
        $years = BudgetYear::orderBy('year', 'desc')->get();

        $appropriations = Appropriation::with(['aipItem.fundSource', 'budgetYear', 'aipItem.ppsa', 'aipItem.budgetClassification'])->get();

        // Basic summary data for the dashboard
        $summary = $appropriations->groupBy('fund_source_id')
            ->map(function ($items) {
                return [
                    'fund_source' => $items->first()->fundSource->name,
                    'total_appropriation' => $items->sum('appropriated_amount'),
                    'total_allotment' => $items->sum('allotment'),
                    'total_obligation' => $items->sum('obligation'),
                    'total_balance' => $items->sum('balance'),
                ];
            })->values();

        // Define Category keywords for Capital Outlay
        $coKeywords = ['MACHINERY', 'EQUIPMENT', 'VEHICLE', 'FURNITURE', 'CONSTRUCT', 'SYSTEM', 'ICT', 'INFRASTRUCTURE', 'CO'];

        $mooeData = $appropriations->filter(function ($item) use ($coKeywords) {
            $ppsaName = strtoupper($item->ppsa->name);
            foreach ($coKeywords as $keyword) {
                if (str_contains($ppsaName, $keyword))
                    return false;
            }
            return true;
        })->groupBy('ppsa_id')
            ->map(function ($items) {
                $catName = $items->first()->ppsa->name;
                return [
                    'category' => strlen($catName) > 25 ? substr($catName, 0, 22) . '...' : $catName,
                    'budget' => $items->sum('appropriated_amount'),
                    'obligated' => $items->sum('obligation'),
                    'balance' => $items->sum('balance'),
                ];
            })->values();

        $coData = $appropriations->filter(function ($item) use ($coKeywords) {
            $ppsaName = strtoupper($item->ppsa->name);
            foreach ($coKeywords as $keyword) {
                if (str_contains($ppsaName, $keyword))
                    return true;
            }
            return false;
        })->groupBy('ppa_description')
            ->map(function ($items) {
                $description = $items->first()->ppa_description;
                return [
                    'project' => strlen($description) > 15 ? substr($description, 0, 12) . '...' : $description,
                    'cost' => $items->sum('appropriated_amount'),
                    'expenditures' => $items->sum('obligation'),
                    'balance' => $items->sum('balance'),
                ];
            })->values();

        return Inertia::render('Budget/Dashboard', [
            'years' => $years,
            'summary' => $summary,
            'mooe_data' => $mooeData,
            'co_data' => $coData,
        ]);
    }

    public function index(Request $request)
    {
        $query = Appropriation::with(['aipItem.fundSource', 'budgetYear', 'aipItem.ppsa', 'aipItem.budgetClassification', 'department']);

        if ($request->has('year_id')) {
            $query->where('budget_year_id', $request->year_id);
        }

        if ($request->has('search')) {
            $query->where('ppa_description', 'like', '%' . $request->search . '%');
        }

        $appropriations = $query->paginate(10);

        $currentYear = BudgetYear::where('is_current', true)->first();
        
        $aipQuery = \App\Models\AipItem::with(['budgetClassification', 'fundSource', 'ppsa', 'budgetYear']);
        
        if ($currentYear) {
            $aipQuery->where('budget_year_id', $currentYear->id);
            
            if (auth()->check() && auth()->user()->department?->name !== 'Admin') {
                $userDeptId = auth()->user()->department_id;
                $aipQuery->where(function ($q) use ($userDeptId) {
                    $q->where('department_id', $userDeptId)
                      ->orWhereHas('implementingDepartments', function ($iq) use ($userDeptId) {
                          $iq->where('department_id', $userDeptId);
                      });
                });
            }
        }

        $aip_items = $currentYear ? $aipQuery->get() : collect();


        return Inertia::render('Budget/Appropriations', [
            'appropriations' => $appropriations,
            'filters' => $request->only(['year_id', 'search']),
            'fund_sources' => FundSource::all(),
            'current_budget_year' => BudgetYear::where('is_current', 1)->first(),
            'budget_years' => BudgetYear::all(),
            'budget_classifications' => BudgetClassification::all(),
            'ppsas' => Ppsa::all(),
            'departments' => \App\Models\Department::where('name', '!=', 'Admin')->get(),
            'aip_items' => $aip_items,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'aip_item_id' => 'required|exists:aip_items,id',
            'budget_year_id' => 'required|exists:budget_years,id',
            'account_code' => 'nullable|string',
            'allotment' => 'required|numeric|min:0',
            'obligation' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if (auth()->user()->department?->name !== 'Admin') {
            $validated['department_id'] = auth()->user()->department_id;
        }

        Appropriation::create($validated);

        return redirect()->back()->with('success', 'Appropriation created successfully.');
    }

    public function update(Request $request, Appropriation $appropriation)
    {
        $validated = $request->validate([
            'aip_item_id' => 'required|exists:aip_items,id',
            'budget_year_id' => 'required|exists:budget_years,id',
            'account_code' => 'nullable|string',
            'allotment' => 'required|numeric|min:0',
            'obligation' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if (auth()->user()->department?->name !== 'Admin') {
            $validated['department_id'] = auth()->user()->department_id;
        }

        $appropriation->update($validated);

        return redirect()->back()->with('success', 'Appropriation updated successfully.');
    }

    public function destroy(Appropriation $appropriation)
    {
        $appropriation->delete();

        return redirect()->back()->with('success', 'Appropriation deleted successfully.');
    }
}
