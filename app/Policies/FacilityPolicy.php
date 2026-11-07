<?php

namespace App\Policies;

use App\Models\Facility;
use App\Models\User;

/** Fasilitas ruang dikelola pengelola & admin (halaman kelolanya menyusul di C4). */
class FacilityPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_LAB_HEAD, User::ROLE_ADMIN);
    }

    public function update(User $user, Facility $facility): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Facility $facility): bool
    {
        return $this->create($user);
    }
}
