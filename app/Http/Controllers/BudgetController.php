<?php

namespace App\Http\Controllers;

use App\Models\Appropriation;
use App\Models\BudgetYear;
use App\Models\FundSource;
use App\Models\Ppsa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BudgetController extends Controller
{
    public function dashboard()
    {
        $years = BudgetYear::orderBy('year', 'desc')->get();
        
        $appropriations = Appropriation::with(['fundSource', 'budgetYear', 'ppsa'])->get();

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
                if (str_contains($ppsaName, $keyword)) return false;
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
                if (str_contains($ppsaName, $keyword)) return true;
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
        $query = Appropriation::with(['fundSource', 'budgetYear', 'ppsa', 'department']);

        if ($request->has('year_id')) {
            $query->where('budget_year_id', $request->year_id);
        }

        if ($request->has('search')) {
            $query->where('ppa_description', 'like', '%' . $request->search . '%');
        }

        $appropriations = $query->paginate(10);

        return Inertia::render('Budget/Appropriations', [
            'appropriations' => $appropriations,
            'filters' => $request->only(['year_id', 'search']),
            'fund_sources' => FundSource::all(),
            'budget_years' => BudgetYear::all(),
            'ppsas' => Ppsa::all(),
            'departments' => \App\Models\Department::where('name', '!=', 'Admin')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fund_source_id' => 'required|exists:fund_sources,id',
            'budget_year_id' => 'required|exists:budget_years,id',
            'ppsa_id' => 'required|exists:ppsas,id',
            'appropriation_type' => 'nullable|in:MOOE,Capital Outlay',
            'account_code' => 'nullable|string',
            'ppa_description' => 'required|string',
            'appropriated_amount' => 'required|numeric|min:0',
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
            'fund_source_id' => 'required|exists:fund_sources,id',
            'budget_year_id' => 'required|exists:budget_years,id',
            'ppsa_id' => 'required|exists:ppsas,id',
            'appropriation_type' => 'nullable|in:MOOE,Capital Outlay',
            'account_code' => 'nullable|string',
            'ppa_description' => 'required|string',
            'appropriated_amount' => 'required|numeric|min:0',
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
