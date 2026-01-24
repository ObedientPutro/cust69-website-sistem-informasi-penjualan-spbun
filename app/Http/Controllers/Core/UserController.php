<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $users = $this->userService->getUsers(
            search: $request->input('search'),
            perPage: 10,
            sortColumn: $request->input('sort', 'created_at'),
            sortDirection: $request->input('direction', 'desc')
        );

        return Inertia::render('User/Index', [
            'users'   => $users,
            'filters' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        redirect(route('dashboard'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        try {
            $this->userService->createUser($request->validated());
            return redirect()->back()->with('success', 'User berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan user: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        redirect(route('dashboard'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        redirect(route('dashboard'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            $this->userService->updateUser($user, $request->validated());
            return redirect()->back()->with('success', 'Data user berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui user: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        try {
            $this->userService->deleteUser($user);
            return redirect()->back()->with('success', 'User berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus user: ' . $e->getMessage());
        }
    }

    public function toggleStatus(User $user)
    {
        try {
            $status = $this->userService->toggleStatus($user);
            return redirect()->back()->with('success', "User berhasil {$status}.");
        } catch (\Exception $e) {
            return redirect()->back()->with('warning', $e->getMessage());
        }
    }
}
