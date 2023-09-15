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
            File::ensureDirectoryExists(env('HOME') . '/.dns-manager');
        }
    }

    public function register(): void
    {
        //
    }
}
