<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = User::query()->with('sites');

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('job_title', 'like', "%{$request->search}%");
            });
        }

        // Filter by role
        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'expired') {
                $query->where('expires_at', '<=', now());
            }
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $sites = Site::orderBy('code')->get(['id', 'code']);

        return Inertia::render('users/Create', [
            'sites' => $sites,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'job_title' => 'nullable|string|max:255',
            'role' => 'required|in:admin,user',
            'access_level' => 'required|in:all,selected',
            'expires_at' => 'nullable|date|after:today',
            'is_active' => 'boolean',
            'site_ids' => 'nullable|array',
            'site_ids.*' => 'exists:sites,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'job_title' => $validated['job_title'] ?? null,
            'role' => $validated['role'],
            'access_level' => $validated['access_level'],
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Attach sites if access_level is 'selected'
        if ($validated['access_level'] === 'selected' && !empty($validated['site_ids'])) {
            $user->sites()->attach($validated['site_ids']);
        }

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): Response
    {
        $user->load(['sites']);

        return Inertia::render('users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response
    {
        $user->load('sites');
        $sites = Site::orderBy('code')->get(['id', 'code']);

        return Inertia::render('users/Edit', [
            'user' => array_merge($user->toArray(), [
                'site_ids' => $user->sites->pluck('id')->toArray(),
            ]),
            'sites' => $sites,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'job_title' => 'nullable|string|max:255',
            'role' => 'required|in:admin,user',
            'access_level' => 'required|in:all,selected',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
            'site_ids' => 'nullable|array',
            'site_ids.*' => 'exists:sites,id',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'job_title' => $validated['job_title'] ?? null,
            'role' => $validated['role'],
            'access_level' => $validated['access_level'],
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Update password if provided
        if (!empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        // Sync sites
        if ($validated['access_level'] === 'selected') {
            $user->sites()->sync($validated['site_ids'] ?? []);
        } else {
            $user->sites()->detach();
        }

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Prevent deleting your own account
        if ($user->id === auth()->id()) {
            return redirect()->back()
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
        ]);

        // Prevent deleting your own account
        $idsWithoutSelf = array_filter($request->ids, fn($id) => $id != auth()->id());

        if (count($idsWithoutSelf) === 0) {
            return redirect()->back()
                ->with('error', 'Cannot delete your own account.');
        }

        $count = User::whereIn('id', $idsWithoutSelf)->delete();

        return redirect()->route('users.index')
            ->with('success', "{$count} users deleted successfully.");
    }
}
