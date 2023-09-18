<?php

declare(strict_types=1);

namespace App\DnsProviders\Abilities;

trait SetsCredentials
{
    protected array $credentials;

    public static function apiKey(): string
    {
        // TODO: this is now replicated, fix
        return str_replace('.', '-', parse_url(static::getApiBaseUrl(), PHP_URL_HOST));
    }

    /** @param  array<string, string>  $credentials */
    public function setCredentials(array $credentials): static
    {
        $this->credentials = $credentials;

        return $this;
    }
}
