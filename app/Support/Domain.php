<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

class Domain
{
    public static function getBaseDomain(string $domain): string
    {
        return Str::of($domain)->explode('.')->slice(-2)->implode('.');
    }

    public static function getFullDomain(string $subdomain, string $domain): string
    {
        $domain = self::getBaseDomain($domain);

        return ltrim("{$subdomain}.{$domain}", '.');
    }
}
