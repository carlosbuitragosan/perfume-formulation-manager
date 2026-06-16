<?php

namespace App\Policies;

use App\Models\BlendVersion;
use App\Models\User;

class BlendVersionPolicy
{
    public function view(User $user, BlendVersion $blendVersion): bool
    {
        return $blendVersion->blend->user_id === $user->id;
    }

    public function update(User $user, BlendVersion $blendVersion): bool
    {
        return $user->id === $blendVersion->blend->user_id;
    }

    public function delete(User $user, BlendVersion $blendVersion): bool
    {
        return $user->id === $blendVersion->blend->user_id;
    }
}
