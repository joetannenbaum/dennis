<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class Config
{
    protected string $path;

    /** @var array<string, mixed> */
    protected array $config;

    public function __construct()
    {
        $this->path = config('app.config_directory') . '/config.json';
        $this->createConfigFileIfMissing();
        $this->cacheConfig();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        Arr::set($this->config, $key, $value);
        $this->writeAndRefreshCache();
    }

    public function remove(string $key): void
    {
        Arr::forget($this->config, $key);
        $this->writeAndRefreshCache();
    }

    protected function writeAndRefreshCache(): void
    {
        $this->writeConfig();
        $this->cacheConfig();
    }

    protected function writeConfig(): void
    {
        File::put(
            $this->path,
            json_encode($this->config, JSON_PRETTY_PRINT),
        );
    }

    protected function cacheConfig(): void
    {
        $this->config = File::json($this->path) ?: [];
    }

    protected function createConfigFileIfMissing(): void
    {
        if (File::missing($this->path)) {
            $this->config = [];
            $this->writeConfig();
        }
    }
}
