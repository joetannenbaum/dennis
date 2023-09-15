<?php

declare(strict_types=1);

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\select;

class AddAccount extends Command
{
    protected $signature = 'account:add';

    protected $description = 'Command description';

    public function handle()
    {
        $providers = collect(config('dns.providers'));

        $providerName = select(
            label: 'Which DNS provider do you want to add?',
            options: $providers->map(fn ($provider) => $provider::getName()),
        );

        $provider = $providers->first(fn ($provider) => $provider::getName() === $providerName);

        app($provider)->setUpNewCredentials();
    }
}
