<?php

declare(strict_types=1);

namespace App\DnsProviders;

use App\Contracts\Provider as ProviderContract;
use App\Data\Record;
use App\Support\Config;
use App\Support\InteractsWithConfig;
use Exception;
use Illuminate\Support\Collection;

use function App\Validation\miniTask;
use function Laravel\Prompts\error;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class Provider
{
    use InteractsWithConfig;

    protected Config $config;

    public function __construct(protected ProviderContract $provider, Config $config = null)
    {
        $this->config = $config ?? new Config();
    }

    public static function with(ProviderContract $provider, Config $config = null): static
    {
        return new static($provider, $config);
    }

    public function addRecord(Record $record): bool
    {
        $value = $this->provider->prepareValue($record);

        try {
            if ($this->provider->getRecord($record)) {
                miniTask("Updating existing {$record->type->value} record for {$record->name} to", $value);

                $this->provider->updateRecord($record);

                return true;
            }

            miniTask("Adding new {$record->type->value} record for {$record->name} pointing to", $value);

            $this->provider->addRecord($record);

            return true;
        } catch (Exception $e) {
            error($e->getMessage());

            return false;
        }
    }

    public function setCredentials(array $credentials): static
    {
        $this->provider->setCredentials($credentials);

        return $this;
    }

    public function domains(): Collection
    {
        return $this->provider->domains();
    }

    public function setDomain(string $domain): static
    {
        $this->provider->setDomain($domain);

        return $this;
    }

    public function getDomain(): string
    {
        return $this->provider->getDomain();
    }

    public function addDomain(): void
    {
        $this->provider->addDomain();
    }

    public function updateNameservers(array $nameservers): void
    {
        $this->provider->updateNameservers($nameservers);
    }

    public function getNameservers(): array
    {
        return $this->provider->getNameservers();
    }

    /** @return Collection<int, Record> */
    public function records(): Collection
    {
        return $this->provider->records();
    }

    public function setUpNewCredentials(): bool
    {
        $credentials = $this->provider->addNewCredentials();

        $this->provider->setCredentials($credentials);

        if (!$this->provider->credentialsAreValid()) {
            warning('It seems that your credentials are invalid, try again.');

            return $this->setUpNewCredentials();
        }

        $name = text(
            label: 'Account Name',
            required: true,
            validate: function ($value) {
                if ($this->getApiConfigValue($this->provider::apiKey(), $value)) {
                    return 'This name is already taken.';
                }
            }
        );

        $this->setApiConfigValue($this->provider::apiKey(), $name, $credentials);

        return true;
    }
}
