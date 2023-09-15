<?php

declare(strict_types=1);

namespace App\Support;

use App\DnsProviders\AbstractDnsProvider;
use Illuminate\Support\Str;

use function Laravel\Prompts\suggest;

trait SelectsADomain
{
    use SelectsAnAccount;

    protected function selectDomain(Config $config): AbstractDnsProvider
    {
        $provider = $this->selectAccount($config);

        $domains = collect($provider->listDomains());

        $selectedDomain = suggest(
            label: 'Domain',
            options: fn ($value) => $value === ''
                ? $domains->toArray()
                : $domains->filter(fn ($domain) => Str::contains($domain, $value, true))->toArray(),
            required: true,
            validate: fn ($domain) => $domains->contains($domain) ? null : 'Invalid domain',
        );

        return $provider->setDomain($selectedDomain);
    }
}
