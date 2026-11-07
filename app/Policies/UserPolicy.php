<?php

namespace App\Policies;

use App\Models\User;

/**
 * Kelola akun (C5): hanya admin. Admin tidak boleh menurunkan role atau
 * menonaktifkan dirinya sendiri (ditegakkan UpdateUserRequest).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $target): bool
    {
        return $this->viewAny($user);
    }
}
