<?php

namespace App\Providers;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Phar;
use Symfony\Component\Console\Helper\TableStyle;

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
