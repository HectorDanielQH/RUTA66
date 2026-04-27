<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\CashRegister;
use Illuminate\Auth\Access\HandlesAuthorization;

class CashRegisterPolicy
{
    use HandlesAuthorization;

    private function canAccessOwnRecord(AuthUser $authUser, CashRegister $cashRegister): bool
    {
        return $authUser->hasRole('super_admin') || $cashRegister->user_id === $authUser->getKey();
    }
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CashRegister');
    }

    public function view(AuthUser $authUser, CashRegister $cashRegister): bool
    {
        return $authUser->can('View:CashRegister') && $this->canAccessOwnRecord($authUser, $cashRegister);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CashRegister');
    }

    public function update(AuthUser $authUser, CashRegister $cashRegister): bool
    {
        return $authUser->can('Update:CashRegister') && $this->canAccessOwnRecord($authUser, $cashRegister);
    }

    public function delete(AuthUser $authUser, CashRegister $cashRegister): bool
    {
        return $authUser->can('Delete:CashRegister') && $this->canAccessOwnRecord($authUser, $cashRegister);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CashRegister');
    }

    public function restore(AuthUser $authUser, CashRegister $cashRegister): bool
    {
        return $authUser->can('Restore:CashRegister') && $this->canAccessOwnRecord($authUser, $cashRegister);
    }

    public function forceDelete(AuthUser $authUser, CashRegister $cashRegister): bool
    {
        return $authUser->can('ForceDelete:CashRegister') && $this->canAccessOwnRecord($authUser, $cashRegister);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CashRegister');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CashRegister');
    }

    public function replicate(AuthUser $authUser, CashRegister $cashRegister): bool
    {
        return $authUser->can('Replicate:CashRegister') && $this->canAccessOwnRecord($authUser, $cashRegister);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CashRegister');
    }

}
