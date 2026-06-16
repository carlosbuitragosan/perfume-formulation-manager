<?php

namespace App\Policies;

use App\Models\Perfume;
use App\Models\User;

class PerfumePolicy
{
    public function view(User $user, Perfume $perfume): bool
    {
        return $perfume->blendVersion->blend->user_id === $user->id;
    }

    public function update(User $user, Perfume $perfume): bool
    {
        return $perfume->blendVersion->blend->user_id === $user->id;
    }

    public function delete(User $user, Perfume $perfume): bool
    {
        return $perfume->blendVersion->blend->user_id === $user->id;
    }
}
