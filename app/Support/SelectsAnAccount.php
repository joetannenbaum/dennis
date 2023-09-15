<?php

declare(strict_types=1);

namespace App\Support;

use App\DnsProviders\AbstractDnsProvider;

use function Laravel\Prompts\select;

trait SelectsAnAccount
{
    protected function selectAccount(Config $config): AbstractDnsProvider
    {
        $credentials = collect($config->get('credentials'));

        $providers = collect(config('dns.providers'))->mapWithKeys(
            fn ($provider) => [$provider::apiKey() => $provider]
        );

        $accounts = $credentials->flatMap(
            fn ($creds, $key) => collect($creds)
                ->map(fn ($credentials, $name) => [
                    'name'        => $name,
                    'provider'    => $providers->get($key),
                    'credentials' => $credentials,
                ])
                ->map(fn ($config) => $config + [
                    'label' => $config['provider']::getName() === $config['name']
                        ? $config['name']
                        : sprintf(
                            '%s (%s)',
                            $config['name'],
                            $config['provider']::getName(),
                        ),
                ])
        )->values();

        $account = select(
            label: 'Account',
            options: $accounts->pluck('label'),
        );

        $provider = $accounts->firstWhere('label', $account);

        return app($provider['provider'])->setCredentials($provider['credentials']);
    }
}
