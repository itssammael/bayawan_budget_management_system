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
        $query = ProcurementTransaction::with(['appropriation.aipItem.fundSource', 'appropriation.budgetYear', 'department'])
            ->orderBy('created_at', 'desc');

        if (auth()->check() && auth()->user()->department?->name !== 'Admin') {
            $query->where('department_id', auth()->user()->department_id);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('item_description', 'like', '%' . $request->search . '%')
                  ->orWhere('transaction_no', 'like', '%' . $request->search . '%')
                  ->orWhere('pr_no', 'like', '%' . $request->search . '%')
                  ->orWhere('po_no', 'like', '%' . $request->search . '%')
                  ->orWhere('ppmp_no', 'like', '%' . $request->search . '%');
            });
        }

        $transactions = $query->paginate(15)->withQueryString();

        return Inertia::render('Budget/ProcurementTracker', [
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
            'ppmp_no' => 'nullable|string',
            'pr_no' => 'nullable|string',
            'po_no' => 'nullable|string',
            'status' => 'required|string',
            'estimated_cost' => 'required|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if (auth()->user()->department?->name !== 'Admin') {
            $validated['department_id'] = auth()->user()->department_id;
        }

        ProcurementTransaction::create($validated);

        return redirect()->back()->with('success', 'Transaction created successfully.');
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.appropriation_id' => 'required|exists:appropriations,id',
            'items.*.transaction_no' => 'required|string|unique:procurement_transactions,transaction_no',
            'items.*.item_description' => 'required|string',
            'items.*.ppmp_no' => 'nullable|string',
            'items.*.pr_no' => 'nullable|string',
            'items.*.po_no' => 'nullable|string',
            'items.*.status' => 'required|string',
            'items.*.estimated_cost' => 'required|numeric|min:0',
            'items.*.actual_cost' => 'nullable|numeric|min:0',
            'items.*.started_at' => 'nullable|date',
            'items.*.completed_at' => 'nullable|date',
            'items.*.department_id' => 'nullable|exists:departments,id',
        ]);

        $items = $request->input('items');

        \DB::transaction(function () use ($items) {
            foreach ($items as $itemData) {
                if (auth()->user()->department?->name !== 'Admin') {
                    $itemData['department_id'] = auth()->user()->department_id;
                }
                ProcurementTransaction::create($itemData);
            }
        });

        return redirect()->back()->with('success', count($items) . ' Transactions created successfully.');
    }

    public function update(Request $request, ProcurementTransaction $procurement)
    {
        $validated = $request->validate([
            'appropriation_id' => 'required|exists:appropriations,id',
            'transaction_no' => 'required|string|unique:procurement_transactions,transaction_no,' . $procurement->id,
            'item_description' => 'required|string',
            'ppmp_no' => 'nullable|string',
            'pr_no' => 'nullable|string',
            'po_no' => 'nullable|string',
            'status' => 'required|string',
            'estimated_cost' => 'required|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
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
