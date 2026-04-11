<?php

namespace App\Http\Controllers;

use App\Models\FundSource;
use App\Models\BudgetYear;
use App\Models\Ppsa;
use App\Models\BudgetClassification;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index(Request $request)
    {

        $tab = $request->get('tab', 'fund_sources');
        $searchPpsa = $request->get('search_ppsa');

        $ppsasQuery = Ppsa::query();
        if ($searchPpsa) {
            $ppsasQuery->where('name', 'like', '%' . $searchPpsa . '%');
        }

        return Inertia::render('Settings/Index', [
            'active_tab' => $tab,
            'filters' => [
                'search_ppsa' => $searchPpsa,
            ],
            'fund_sources' => FundSource::paginate(12, ['*'], 'fs_page')->withQueryString(),
            'budget_years' => BudgetYear::orderBy('year', 'desc')->paginate(12, ['*'], 'by_page')->withQueryString(),
            'ppsas' => $ppsasQuery->paginate(12, ['*'], 'ppsa_page')->withQueryString(),
            'budget_classifications' => BudgetClassification::paginate(12, ['*'], 'bc_page')->withQueryString(),
            'activity_logs' => ActivityLog::with(['user', 'subject'])->latest()->paginate(20, ['*'], 'log_page')->withQueryString(),
            'system_settings' => SystemSetting::all()->pluck('value', 'key'),
        ]);
    }

    public function updateAppearance(Request $request)
    {
        $request->validate([
            'org_name' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'icon' => 'nullable|image|max:1024',
            'header_color' => 'nullable|string|size:7',
            'header_text_color' => 'nullable|string|size:7',
            'bg_color' => 'nullable|string|size:7',
            'accent_color' => 'nullable|string|size:7',
        ]);

        if ($request->exists('org_name')) {
            SystemSetting::set('org_name', $request->org_name);
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('system', 'public');
            SystemSetting::set('system_logo', Storage::url($path));
        }

        if ($request->hasFile('icon')) {
            $path = $request->file('icon')->store('system', 'public');
            SystemSetting::set('system_icon', Storage::url($path));
        }

        if ($request->has('header_color')) {
            SystemSetting::set('header_color', $request->header_color);
        }

        if ($request->has('header_text_color')) {
            SystemSetting::set('header_text_color', $request->header_text_color);
        }

        if ($request->has('bg_color')) {
            SystemSetting::set('bg_color', $request->bg_color);
        }

        if ($request->has('accent_color')) {
            SystemSetting::set('accent_color', $request->accent_color);
        }

        return redirect()->back()->with('success', 'System appearance updated successfully.');
    }

    // Fund Source CRUD
    public function storeFundSource(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:fund_sources,name',
            'description' => 'nullable|string',
        ]);

        FundSource::create($validated);

        return redirect()->back()->with('success', 'Fund Source created successfully.');
    }

    public function updateFundSource(Request $request, FundSource $fundSource)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:fund_sources,name,' . $fundSource->id,
            'description' => 'nullable|string',
        ]);

        $fundSource->update($validated);

        return redirect()->back()->with('success', 'Fund Source updated successfully.');
    }

    public function destroyFundSource(FundSource $fundSource)
    {
        $fundSource->delete();

        return redirect()->back()->with('success', 'Fund Source deleted successfully.');
    }

    // Budget Year CRUD
    public function storeBudgetYear(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100|unique:budget_years,year',
        ]);

        BudgetYear::create($validated);

        return redirect()->back()->with('success', 'Budget Year created successfully.');
    }

    public function updateBudgetYear(Request $request, BudgetYear $budgetYear)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100|unique:budget_years,year,' . $budgetYear->id,
        ]);

        $budgetYear->update($validated);

        return redirect()->back()->with('success', 'Budget Year updated successfully.');
    }

    public function setCurrentBudgetYear(BudgetYear $budgetYear)
    {
        BudgetYear::query()->update(['is_current' => 0]);
        $budgetYear->update(['is_current' => 1]);

        return redirect()->back()->with('success', "Budget Year {$budgetYear->year} set as current successfully.");
    }

    // PPSA CRUD
    public function storePpsa(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ppsas,name',
        ]);

        Ppsa::create($validated);

        return redirect()->back()->with('success', 'PPSA created successfully.');
    }

    public function updatePpsa(Request $request, Ppsa $ppsa)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ppsas,name,' . $ppsa->id,
        ]);

        $ppsa->update($validated);

        return redirect()->back()->with('success', 'PPSA updated successfully.');
    }

    public function destroyPpsa(Ppsa $ppsa)
    {
        $ppsa->delete();

        return redirect()->back()->with('success', 'PPSA deleted successfully.');
    }

    // Budget Classification CRUD
    public function storeBudgetClassification(Request $request)
    {
        $validated = $request->validate([
            'classification_name' => 'required|string|max:255|unique:budget_classifications,classification_name',
        ]);

        BudgetClassification::create($validated);

        return redirect()->back()->with('success', 'Budget Classification created successfully.');
    }

    public function updateBudgetClassification(Request $request, BudgetClassification $budgetClassification)
    {
        $validated = $request->validate([
            'classification_name' => 'required|string|max:255|unique:budget_classifications,classification_name,' . $budgetClassification->id,
        ]);

        $budgetClassification->update($validated);

        return redirect()->back()->with('success', 'Budget Classification updated successfully.');
    }

    public function destroyBudgetClassification(BudgetClassification $budgetClassification)
    {
        $budgetClassification->delete();

        return redirect()->back()->with('success', 'Budget Classification deleted successfully.');
    }
}
