<?php

namespace App\Http\Controllers;

use App\Models\AipItem;
use App\Models\FundSource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AipController extends Controller
{
    public function index(Request $request)
    {
        $query = AipItem::with(['fundSource', 'department']);

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
            'departments' => \App\Models\Department::where('name', '!=', 'Admin')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fund_source_id' => 'required|exists:fund_sources,id',
            'aip_reference_code' => 'required|string|unique:aip_items,aip_reference_code',
            'ppa_description' => 'required|string',
            'department_id' => 'required|exists:departments,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'expected_outputs' => 'nullable|string',
            'amount_ps' => 'required|numeric|min:0',
            'amount_mooe' => 'required|numeric|min:0',
            'amount_fe' => 'required|numeric|min:0',
            'amount_co' => 'required|numeric|min:0',
        ]);

        if (auth()->user()->department?->name !== 'Admin') {
            $validated['department_id'] = auth()->user()->department_id;
        }

        AipItem::create($validated);

        return redirect()->back()->with('success', 'AIP Item created successfully.');
    }

    public function update(Request $request, AipItem $aip)
    {
        $validated = $request->validate([
            'fund_source_id' => 'required|exists:fund_sources,id',
            'aip_reference_code' => 'required|string|unique:aip_items,aip_reference_code,' . $aip->id,
            'ppa_description' => 'required|string',
            'department_id' => 'required|exists:departments,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'expected_outputs' => 'nullable|string',
            'amount_ps' => 'required|numeric|min:0',
            'amount_mooe' => 'required|numeric|min:0',
            'amount_fe' => 'required|numeric|min:0',
            'amount_co' => 'required|numeric|min:0',
        ]);

        if (auth()->user()->department?->name !== 'Admin') {
            $validated['department_id'] = auth()->user()->department_id;
        }

        $aip->update($validated);

        return redirect()->back()->with('success', 'AIP Item updated successfully.');
    }

    public function destroy(AipItem $aip)
    {
        $aip->delete();

        return redirect()->back()->with('success', 'AIP Item deleted successfully.');
    }
}
