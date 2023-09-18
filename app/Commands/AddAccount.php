<?php

declare(strict_types=1);

namespace App\Commands;

use App\DnsProviders\Provider;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\select;

class AddAccount extends Command
{
    protected $signature = 'accounts:add';

    protected $description = 'Add a DNS provider account.';

    public function handle(): void
    {
        intro('Add Account');

        /** @var \Illuminate\Support\Collection<int, string> $providers */
        $providers = collect(config('dns.providers'));

        $providerName = select(
            label: 'Which DNS provider do you want to add?',
            options: $providers->map(fn (string $provider) => $provider::getName()),
        );

        $provider = $providers->first(fn (string $provider) => $provider::getName() === $providerName);

        Provider::with(new $provider)->setUpNewCredentials();

        intro('Account added!');
    }
}
