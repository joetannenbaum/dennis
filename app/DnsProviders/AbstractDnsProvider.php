<?php

declare(strict_types=1);

namespace App\DnsProviders;

use App\Data\Record;
use App\Support\Config;
use App\Support\InteractsWithConfig;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use function App\Validation\miniTask;
use function Laravel\Prompts\error;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

abstract class AbstractDnsProvider
{
    use InteractsWithConfig;

    protected string $domain;

    protected array $credentials;

    public function __construct(
        protected Config $config,
    ) {
    }

    public static function apiKey(): string
    {
        // TODO: this is now replicated, fix
        return str_replace('.', '-', parse_url(static::getApiBaseUrl(), PHP_URL_HOST));
    }

    public static function getName(): string
    {
        return class_basename(get_called_class());
    }

    public function setCredentials(array $credentials): static
    {
        $this->credentials = $credentials;

        return $this;
    }

    public function setDomain(string $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    /** @return Collection<string> */
    abstract public function listDomains(): Collection;

    public function setUpNewCredentials(): bool
    {
        $credentials = $this->addNewCredentials();

        $this->setCredentials($credentials);

        if (!$this->credentialsAreValid()) {
            warning('It seems that your credentials are invalid, try again.');

            return $this->setUpNewCredentials();
        }

        $name = text(
            label: 'Account Name',
            required: true,
            validate: function ($value) {
                if ($this->getApiConfigValue(self::apiKey(), $value)) {
                    return 'This name is already taken.';
                }
            }
        );

        $this->setConfig($name, $credentials);

        return true;
    }

    public function addRecord(Record $record): bool
    {
        $value = $this->prepValue($record);

        try {
            if ($this->getRecord($record)) {
                miniTask("Updating existing {$record->type->value} record for {$record->name} to", $value);

                $this->updateProviderRecord($record);

                return true;
            }

            miniTask("Adding new {$record->type->value} record for {$record->name} pointing to", $value);

            $this->addProviderRecord($record);

            return true;
        } catch (Exception $e) {
            error($e->getMessage());

            return false;
        }
    }

    /** @return Collection<Record> */
    abstract public function listRecords(): Collection;

    /** @return array<int, string> */
    abstract public function getNameservers(): array;

    /** @param  array<int, string>  $nameservers */
    abstract public function updateNameservers(array $nameservers);

    abstract public function addDomain();

    protected function prepValue(Record $record): string
    {
        return $record->value;
    }

    abstract protected static function getApiBaseUrl(): string;

    abstract protected function getRecord(Record $record): mixed;

    abstract protected function addProviderRecord(Record $record): void;

    abstract protected function updateProviderRecord(Record $record): void;

    /** @return array<string, string> */
    abstract protected function addNewCredentials(): array;

    abstract protected function credentialsAreValid(): bool;

    protected function withTrailingDot(string $value): string
    {
        return $value === '@' ? $value : Str::finish($value, '.');
    }

    /** @param  array<string, string>  $payload */
    protected function setConfig(string $name, array $payload): void
    {
        $this->setApiConfigValue(self::apiKey(), $name, $payload);
    }
}
