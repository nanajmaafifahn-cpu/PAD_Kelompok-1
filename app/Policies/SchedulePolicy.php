<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

/** Semua user login boleh melihat jadwal; hanya pengelola & admin yang mengubah. */
class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_LAB_HEAD, User::ROLE_ADMIN);
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $this->create($user);
    }
}
