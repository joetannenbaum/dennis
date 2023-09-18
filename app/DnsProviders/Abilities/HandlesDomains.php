<?php

declare(strict_types=1);

namespace App\DnsProviders\Abilities;

trait HandlesDomains
{
    protected string $domain;

    public function setDomain(string $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }
}
