<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

/** Semua user login boleh melihat ruang; hanya pengelola & admin yang mengubah. */
class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_LAB_HEAD, User::ROLE_ADMIN);
    }

    public function update(User $user, Room $room): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Room $room): bool
    {
        return $this->create($user);
    }
}
