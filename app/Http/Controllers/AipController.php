<?php

namespace App\Http\Controllers;

use App\Models\AipItem;
use App\Models\FundSource;
use App\Models\BudgetYear;
use App\Models\BudgetClassification;
use App\Models\Ppsa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AipController extends Controller
{
    public function index(Request $request)
    {
        $query = AipItem::with(['fundSource', 'department', 'budgetYear', 'budgetClassification', 'ppsa', 'implementingDepartments.department']);

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('ppa_description', 'like', '%' . $request->search . '%')
                  ->orWhere('aip_reference_code', 'like', '%' . $request->search . '%')
                  ->orWhereHas('department', function ($dq) use ($request) {
                      $dq->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $items = $query->paginate(15)->withQueryString();

        return Inertia::render('Budget/AipItems', [
            'items' => $items,
            'filters' => $request->only(['search']),
            'fund_sources' => FundSource::all(),
            'budget_years' => BudgetYear::orderBy('year', 'desc')->get(),
            'budget_classifications' => BudgetClassification::all(),
            'ppsas' => Ppsa::all(),
            'departments' => \App\Models\Department::where('name', '!=', 'Admin')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'budget_year_id' => 'required|exists:budget_years,id',
            'budget_classification_id' => 'required|exists:budget_classifications,id',
            'fund_source_id' => 'required|exists:fund_sources,id',
            'ppsa_id' => 'nullable|exists:ppsas,id',
            'aip_reference_code' => 'required|string|unique:aip_items,aip_reference_code',
            'ppa_description' => 'required|string',
            'department_id' => 'required|exists:departments,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'expected_outputs' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'implementing_departments' => 'nullable|array',
            'implementing_departments.*.department_id' => 'required|exists:departments,id',
            'implementing_departments.*.amount' => 'required|numeric|min:0',
        ]);

        if (auth()->user()->department?->name !== 'Admin') {
            $validated['department_id'] = auth()->user()->department_id;
        }

        \DB::transaction(function () use ($validated) {
            $implementingDepartments = $validated['implementing_departments'] ?? [];
            unset($validated['implementing_departments']);
            
            $aip = AipItem::create($validated);
            
            foreach ($implementingDepartments as $dept) {
                $aip->implementingDepartments()->create($dept);
            }
        });

        return redirect()->back()->with('success', 'AIP Item created successfully.');
    }

    public function update(Request $request, AipItem $aip)
    {
        $validated = $request->validate([
            'budget_year_id' => 'required|exists:budget_years,id',
            'budget_classification_id' => 'required|exists:budget_classifications,id',
            'fund_source_id' => 'required|exists:fund_sources,id',
            'ppsa_id' => 'nullable|exists:ppsas,id',
            'aip_reference_code' => 'required|string|unique:aip_items,aip_reference_code,' . $aip->id,
            'ppa_description' => 'required|string',
            'department_id' => 'required|exists:departments,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'expected_outputs' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'implementing_departments' => 'nullable|array',
            'implementing_departments.*.department_id' => 'required|exists:departments,id',
            'implementing_departments.*.amount' => 'required|numeric|min:0',
        ]);

        if (auth()->user()->department?->name !== 'Admin') {
            $validated['department_id'] = auth()->user()->department_id;
        }

        \DB::transaction(function () use ($validated, $aip) {
            $implementingDepartments = $validated['implementing_departments'] ?? [];
            unset($validated['implementing_departments']);
            
            $aip->update($validated);
            
            $aip->implementingDepartments()->delete();
            foreach ($implementingDepartments as $dept) {
                $aip->implementingDepartments()->create($dept);
            }
        });

        return redirect()->back()->with('success', 'AIP Item updated successfully.');
    }

    public function destroy(AipItem $aip)
    {
        $aip->delete();

        return redirect()->back()->with('success', 'AIP Item deleted successfully.');
    }
}
