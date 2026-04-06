<?php

namespace App\Policies;

use App\Models\Bottle;
use App\Models\User;

class BottlePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Bottle $bottle): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Bottle $bottle): bool
    {
        return $user->id === $bottle->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Bottle $bottle): bool
    {
        return $user->id === $bottle->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Bottle $bottle): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Bottle $bottle): bool
    {
        return false;
    }
}
