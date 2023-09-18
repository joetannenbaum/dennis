<?php

declare(strict_types=1);

namespace App\DnsProviders\Abilities;

use Illuminate\Support\Str;

trait PreparesValues
{
    protected function withTrailingDot(string $value): string
    {
        return $value === '@' ? $value : Str::finish($value, '.');
    }
}
