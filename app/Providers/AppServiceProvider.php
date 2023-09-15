<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Phar;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (Phar::running()) {
            File::ensureDirectoryExists(config('app.config_directory'));
        }
    }

    public function register(): void
    {
        //
    }
}
