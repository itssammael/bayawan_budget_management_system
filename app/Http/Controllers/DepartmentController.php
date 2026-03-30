<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('admin') && !(auth()->user()->department && auth()->user()->department->name === 'Admin')) {
            abort(403, 'Unauthorized action.');
        }

        $query = \App\Models\Department::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('shortname', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('department_head', 'like', "%{$search}%");
        }

        $departments = $query->latest()->paginate(10)->withQueryString();

        return \Inertia\Inertia::render('Departments/Index', [
            'departments' => $departments,
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('admin') && !(auth()->user()->department && auth()->user()->department->name === 'Admin')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'shortname' => 'nullable|string|max:100',
            'code' => 'nullable|string|max:50',
            'department_head' => 'nullable|string|max:255',
        ]);

        \App\Models\Department::create($validated);

        return redirect()->route('departments.index')->with('success', 'Department created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\Department $department)
    {
        if (!auth()->user()->hasRole('admin') && !(auth()->user()->department && auth()->user()->department->name === 'Admin')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'shortname' => 'nullable|string|max:100',
            'code' => 'nullable|string|max:50',
            'department_head' => 'nullable|string|max:255',
        ]);

        $department->update($validated);

        return redirect()->route('departments.index')->with('success', 'Department updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\Department $department)
    {
        if (!auth()->user()->hasRole('admin') && !(auth()->user()->department && auth()->user()->department->name === 'Admin')) {
            abort(403, 'Unauthorized action.');
        }

        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Department deleted successfully.');
    }
}
