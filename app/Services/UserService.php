<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserService
{
    /**
     * Mengambil data user dengan Search, Pagination, dan Sorting.
     */
    public function getUsers(
        string $search = null,
        int $perPage = 10,
        string $sortColumn = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator
    {
        $allowedSorts = ['name', 'email', 'role', 'nip', 'is_active', 'created_at'];

        if (!in_array($sortColumn, $allowedSorts)) {
            $sortColumn = 'created_at';
        }

        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return User::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            })
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Membuat user baru dengan Transaction.
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $data['photo'] = $data['photo']->store('users/photos', 'public');
            }

            return User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'role'      => $data['role'],
                'nip'       => $data['nip'] ?? null,
                'phone'     => $data['phone'] ?? null,
                'address'   => $data['address'] ?? null,
                'is_active' => true,
                'photo'     => $data['photo'] ?? null,
            ]);
        });
    }

    /**
     * Update user dengan Transaction.
     */
    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = Hash::make($data['password']);
            }

            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                if ($user->photo) {
                    Storage::disk('public')->delete($user->photo);
                }
                $data['photo'] = $data['photo']->store('users/photos', 'public');
            } else {
                unset($data['photo']);
            }

            $user->update($data);
            return $user;
        });
    }

    /**
     * Menghapus user dengan pengecekan relasi data.
     */
    public function deleteUser(User $user): void
    {
        if (auth()->id() === $user->id) {
            throw new \Exception('Anda tidak dapat menghapus akun Anda sendiri.');
        }

        try {
            DB::transaction(function () use ($user) {
                if ($user->photo) {
                    Storage::disk('public')->delete($user->photo);
                }
                $user->delete();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new \Exception('User tidak dapat dihapus karena memiliki riwayat transaksi atau data terkait lainnya.');
            }
            throw $e;
        }
    }

    /**
     * Toggle status aktif/non-aktif (Atomic Update).
     */
    public function toggleStatus(User $user): string
    {
        if (auth()->id() === $user->id) {
            throw new \Exception('Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->update(['is_active' => !$user->is_active]);

        return $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
    }
}
