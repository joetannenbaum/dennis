<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Arr;

trait InteractsWithConfig
{
    protected function getAllConfigsForApi(string $host): mixed
    {
        return $this->config->get($this->getApiConfigKey($host));
    }

    protected function setApiConfigValue(string $host, string $key, mixed $value): void
    {
        $this->config->set($this->getApiConfigKey($host) . '.' . $key, $value);
    }

    protected function getApiConfigValue(string $host, string $key, mixed $default = null): mixed
    {
        return Arr::get($this->getAllConfigsForApi($host), $key, $default);
    }

    protected function getApiConfigKey(string $host): string
    {
        return 'credentials.' . str_replace('.', '-', $host);
    }
}
