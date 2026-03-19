<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('Users/Index', [
            'users' => User::query()
                ->with(['roles', 'department'])
                ->when($request->input('search'), function ($query, $search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                })
                ->paginate(10)
                ->withQueryString(),
            'roles' => \App\Models\Role::all(),
            'departments' => auth()->user()->department && auth()->user()->department->name === 'Admin' 
                ? \App\Models\Department::all() 
                : \App\Models\Department::where('id', auth()->user()->department_id)->get(),
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'roles' => 'nullable|array',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $departmentId = $request->department_id;
        if (!(auth()->user()->department && auth()->user()->department->name === 'Admin')) {
            $departmentId = auth()->user()->department_id;
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'department_id' => $departmentId,
        ]);

        if ($request->roles) {
            $user->roles()->sync($request->roles);
        }

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'roles' => 'nullable|array',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $departmentId = $request->department_id;
        if (!(auth()->user()->department && auth()->user()->department->name === 'Admin')) {
            $departmentId = auth()->user()->department_id;
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'department_id' => $departmentId,
        ]);

        if ($request->roles) {
            $user->roles()->sync($request->roles);
        }

        if ($request->password) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'You cannot delete yourself.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
