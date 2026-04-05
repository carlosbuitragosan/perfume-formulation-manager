<?php

namespace App\Policies;

use App\Models\Blend;
use App\Models\User;

class BlendPolicy
{
    public function view(User $user, Blend $blend)
    {
        return $user->id === $blend->user_id;
    }

    public function update(User $user, Blend $blend): bool
    {
        return $blend->user_id === $user->id;
    }
}
