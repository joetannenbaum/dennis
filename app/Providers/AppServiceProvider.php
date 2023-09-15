<?php

namespace App\Providers;

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

        Command::mixin(
            new class
            {
                public function promptsTable()
                {
                    return function (array $headers, array $data) {
                        $box = (new TableStyle())
                            ->setHorizontalBorderChars('─')
                            ->setVerticalBorderChars(' │', '│')
                            ->setCrossingChars('┼', ' ┌', '┬', '─┐', '─┤', '─┘', '┴', ' └', ' ├');

                        return $this->table($headers, $data, $box);
                    };
                }
            }

        );
    }

    public function register(): void
    {
        //
    }
}
