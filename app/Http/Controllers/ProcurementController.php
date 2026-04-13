<?php

namespace App\Http\Controllers;

use App\Models\ProcurementTransaction;
use App\Models\Appropriation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProcurementController extends Controller
{
    public function index(Request $request)
    {
        $query = ProcurementTransaction::with(['appropriation.aipItem.fundSource', 'appropriation.budgetYear', 'department']);

        if ($request->has('search')) {
            $query->where('item_description', 'like', '%' . $request->search . '%')
                  ->orWhere('transaction_no', 'like', '%' . $request->search . '%');
        }

        $transactions = $query->paginate(15);

        return Inertia::render('Budget/CapitalOutlay', [
            'transactions' => $transactions,
            'filters' => $request->only(['search']),
            'appropriations' => Appropriation::with(['aipItem.fundSource', 'budgetYear'])->get()->sortBy('ppa_description')->values(),
            'departments' => \App\Models\Department::where('name', '!=', 'Admin')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'appropriation_id' => 'required|exists:appropriations,id',
            'transaction_no' => 'required|string|unique:procurement_transactions,transaction_no',
            'item_description' => 'required|string',
            'estimated_cost' => 'required|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
            'award_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'status' => 'required|string',
            'remarks' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if (auth()->user()->department?->name !== 'Admin') {
            $validated['department_id'] = auth()->user()->department_id;
        }

        ProcurementTransaction::create($validated);

        return redirect()->back()->with('success', 'Transaction created successfully.');
    }

    public function update(Request $request, ProcurementTransaction $procurement)
    {
        $validated = $request->validate([
            'appropriation_id' => 'required|exists:appropriations,id',
            'transaction_no' => 'required|string|unique:procurement_transactions,transaction_no,' . $procurement->id,
            'item_description' => 'required|string',
            'estimated_cost' => 'required|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
            'award_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'status' => 'required|string',
            'remarks' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if (auth()->user()->department?->name !== 'Admin') {
            $validated['department_id'] = auth()->user()->department_id;
        }

        $procurement->update($validated);

        return redirect()->back()->with('success', 'Transaction updated successfully.');
    }

    public function destroy(ProcurementTransaction $procurement)
    {
        $procurement->delete();

        return redirect()->back()->with('success', 'Transaction deleted successfully.');
    }
}
