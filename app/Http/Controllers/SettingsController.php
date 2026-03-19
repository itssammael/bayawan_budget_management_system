<?php

namespace App\Http\Controllers;

use App\Models\FundSource;
use App\Models\BudgetYear;
use App\Models\BudgetCategory;
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
        $searchCategory = $request->get('search_category');

        $budgetCategoriesQuery = BudgetCategory::query();
        if ($searchCategory) {
            $budgetCategoriesQuery->where('name', 'like', '%' . $searchCategory . '%');
        }

        return Inertia::render('Settings/Index', [
            'active_tab' => $tab,
            'filters' => [
                'search_category' => $searchCategory,
            ],
            'fund_sources' => FundSource::paginate(12, ['*'], 'fs_page')->withQueryString(),
            'budget_years' => BudgetYear::paginate(12, ['*'], 'by_page')->withQueryString(),
            'budget_categories' => $budgetCategoriesQuery->paginate(12, ['*'], 'cat_page')->withQueryString(),
            'activity_logs' => ActivityLog::with(['user', 'subject'])->latest()->paginate(20, ['*'], 'log_page')->withQueryString(),
            'system_settings' => SystemSetting::all()->pluck('value', 'key'),
        ]);
    }

    public function updateAppearance(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|max:2048',
            'icon' => 'nullable|image|max:1024',
            'header_color' => 'nullable|string|size:7',
            'header_text_color' => 'nullable|string|size:7',
            'bg_color' => 'nullable|string|size:7',
            'accent_color' => 'nullable|string|size:7',
        ]);

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

    public function destroyBudgetYear(BudgetYear $budgetYear)
    {
        $budgetYear->delete();

        return redirect()->back()->with('success', 'Budget Year deleted successfully.');
    }

    // Budget Category CRUD
    public function storeBudgetCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:budget_categories,name',
        ]);

        BudgetCategory::create($validated);

        return redirect()->back()->with('success', 'Budget Category created successfully.');
    }

    public function updateBudgetCategory(Request $request, BudgetCategory $budgetCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:budget_categories,name,' . $budgetCategory->id,
        ]);

        $budgetCategory->update($validated);

        return redirect()->back()->with('success', 'Budget Category updated successfully.');
    }

    public function destroyBudgetCategory(BudgetCategory $budgetCategory)
    {
        $budgetCategory->delete();

        return redirect()->back()->with('success', 'Budget Category deleted successfully.');
    }
}
