<?php

namespace App\Policies;

use App\Enums\PlatformPermission;
use App\Models\SenderId;
use App\Models\User;

class SenderIdPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformStaff() || $user->currentCompanyId() !== null;
    }

    public function view(User $user, SenderId $senderId): bool
    {
        if ($user->isPlatformStaff()) {
            return true;
        }

        return $user->currentCompanyId() === $senderId->company_id;
    }

    public function create(User $user): bool
    {
        if ($user->isPlatformStaff()) {
            return false;
        }

        $role = $user->companyRole();

        return $role !== null && $role->canManageSenderIds();
    }

    public function update(User $user, SenderId $senderId): bool
    {
        if ($user->isPlatformStaff()) {
            return false;
        }

        if ($user->currentCompanyId() !== $senderId->company_id) {
            return false;
        }

        $role = $user->companyRole();

        return $role !== null && $role->canManageSenderIds();
    }

    public function delete(User $user, SenderId $senderId): bool
    {
        return $this->update($user, $senderId);
    }

    public function downloadDocument(User $user, SenderId $senderId): bool
    {
        return $this->view($user, $senderId) && $senderId->hasDocument();
    }

    public function review(User $user, SenderId $senderId): bool
    {
        return $user->can(PlatformPermission::SenderIdsReview->value);
    }
}
