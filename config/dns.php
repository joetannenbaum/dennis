<?php

use App\DnsProviders\Cloudflare;
use App\DnsProviders\DigitalOcean;
use App\DnsProviders\GoDaddy;

return [
    'providers' => [
        DigitalOcean::class,
        GoDaddy::class,
        Cloudflare::class,
    ],
];
