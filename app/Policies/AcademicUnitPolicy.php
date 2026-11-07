<?php

namespace App\Policies;

use App\Models\AcademicUnit;
use App\Models\User;

/** Unit akademik (fakultas/jurusan) hanya dikelola admin (C5). */
class AcademicUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, AcademicUnit $unit): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, AcademicUnit $unit): bool
    {
        return $this->viewAny($user);
    }
}
