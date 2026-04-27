<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Order;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    private function canAccessOwnRecord(AuthUser $authUser, Order $order): bool
    {
        return $authUser->hasRole('super_admin')
            || $order->user_id === $authUser->getKey()
            || $order->cashRegister?->user_id === $authUser->getKey();
    }
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Order');
    }

    public function view(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('View:Order') && $this->canAccessOwnRecord($authUser, $order);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Order');
    }

    public function update(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('Update:Order') && $this->canAccessOwnRecord($authUser, $order);
    }

    public function delete(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('Delete:Order') && $this->canAccessOwnRecord($authUser, $order);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Order');
    }

    public function restore(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('Restore:Order') && $this->canAccessOwnRecord($authUser, $order);
    }

    public function forceDelete(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('ForceDelete:Order') && $this->canAccessOwnRecord($authUser, $order);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Order');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Order');
    }

    public function replicate(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can('Replicate:Order') && $this->canAccessOwnRecord($authUser, $order);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Order');
    }

}
