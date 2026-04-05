<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    public function view(User $user, Material $material): bool
    {
        return $user->id === $material->user_id;
    }

    public function update(User $user, Material $material): bool
    {
        return $user->id === $material->user_id;
    }

    public function delete(User $user, Material $material): bool
    {
        return $user->id === $material->user_id;
    }
}
