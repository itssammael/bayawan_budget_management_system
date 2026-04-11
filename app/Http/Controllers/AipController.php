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
        $query = AipItem::orderBy('aip_reference_code', 'asc')->with(['fundSource', 'department', 'budgetYear', 'budgetClassification', 'ppsa', 'implementingDepartments.department']);

        if (auth()->check() && auth()->user()->department?->name !== 'Admin') {
            $userDeptId = auth()->user()->department_id;
            $query->where(function ($q) use ($userDeptId) {
                $q->where('department_id', $userDeptId)
                  ->orWhereHas('implementingDepartments', function ($iq) use ($userDeptId) {
                      $iq->where('department_id', $userDeptId);
                  });
            });
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('ppa_description', 'like', '%' . $request->search . '%')
                  ->orWhere('aip_reference_code', 'like', '%' . $request->search . '%')
                  ->orWhereHas('department', function ($dq) use ($request) {
                      $dq->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $items = $query->paginate(8)->withQueryString();

        return Inertia::render('Budget/AipItems', [
            'items' => $items,
            'filters' => $request->only(['search']),
            'fund_sources' => FundSource::all(),
            'current_budget_year' => BudgetYear::where('is_current', 1)->first(),
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

    public function bulkStore(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.budget_year_id' => 'required|exists:budget_years,id',
            'items.*.budget_classification_id' => 'required|exists:budget_classifications,id',
            'items.*.fund_source_id' => 'required|exists:fund_sources,id',
            'items.*.ppsa_id' => 'nullable|exists:ppsas,id',
            'items.*.aip_reference_code' => 'required|string|unique:aip_items,aip_reference_code',
            'items.*.ppa_description' => 'required|string',
            'items.*.department_id' => 'required|exists:departments,id',
            'items.*.start_date' => 'required|date',
            'items.*.end_date' => 'required|date',
            'items.*.expected_outputs' => 'nullable|string',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.implementing_departments' => 'nullable|array',
            'items.*.implementing_departments.*.department_id' => 'required|exists:departments,id',
            'items.*.implementing_departments.*.amount' => 'required|numeric|min:0',
        ]);

        $items = $request->input('items');

        \DB::transaction(function () use ($items) {
            foreach ($items as $itemData) {
                $implementingDepartments = $itemData['implementing_departments'] ?? [];
                unset($itemData['implementing_departments']);
                
                if (auth()->user()->department?->name !== 'Admin') {
                    $itemData['department_id'] = auth()->user()->department_id;
                }

                $aip = AipItem::create($itemData);
                
                foreach ($implementingDepartments as $dept) {
                    $aip->implementingDepartments()->create($dept);
                }
            }
        });

        return redirect()->back()->with('success', count($items) . ' AIP Items created successfully.');
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
