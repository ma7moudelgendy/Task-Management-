<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bindings between Repository Interfaces and Eloquent Implementations will be registered here
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}

