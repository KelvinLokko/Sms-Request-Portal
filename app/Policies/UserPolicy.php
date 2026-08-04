<?php

namespace App\Policies;

use App\Enums\PlatformPermission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PlatformPermission::UsersManage->value);
    }

    public function view(User $actor, User $subject): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $actor, User $subject): bool
    {
        return $this->viewAny($actor);
    }

    public function delete(User $actor, User $subject): bool
    {
        if (! $this->viewAny($actor)) {
            return false;
        }

        // Nobody may delete their own account — including admins.
        return $actor->id !== $subject->id;
    }
}
