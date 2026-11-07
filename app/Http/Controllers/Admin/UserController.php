<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Kelola akun (C5, khusus admin): daftar, tambah, ubah role, aktif/nonaktif.
 * Akun tidak dihapus agar riwayat booking tetap utuh; cukup dinonaktifkan.
 */
class UserController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('admin.users.index', [
            'users' => User::orderBy('name')->paginate(15),
            'roles' => User::ROLE_LABELS,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.create', ['roles' => User::ROLE_LABELS]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([...$request->validated(), 'is_active' => true]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Akun {$user->email} berhasil dibuat.");
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => User::ROLE_LABELS,
            'isSelf' => $user->is(auth()->user()),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->userData());

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Akun {$user->email} berhasil diperbarui.");
    }
}
