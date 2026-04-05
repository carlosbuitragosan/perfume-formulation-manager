<?php

namespace App\Providers;

use App\Models\Blend;
use App\Models\Material;
use App\Policies\BlendPolicy;
use App\Policies\MaterialPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Blend::class => BlendPolicy::class,
        Material::class => MaterialPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
