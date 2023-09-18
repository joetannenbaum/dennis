<?php

declare(strict_types=1);

namespace App\DnsProviders;

use App\Contracts\Provider;
use App\Data\Record;
use App\DnsProviders\Abilities\HandlesDomains;
use App\DnsProviders\Abilities\PreparesValues;
use App\DnsProviders\Abilities\SetsCredentials;
use App\Enums\RecordType;
use App\Support\Domain;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

use function Laravel\Prompts\info;
use function Laravel\Prompts\password;

class DigitalOcean implements Provider
{
    use HandlesDomains, PreparesValues, SetsCredentials;

    public static function getName(): string
    {
        return 'DigitalOcean';
    }

    public static function getApiBaseUrl(): string
    {
        return 'https://api.digitalocean.com/v2/';
    }

    public function addDomain(): void
    {
        if ($this->getExistingDomain() === null) {
            $this->client()->post('domains', [
                'name' => $this->domain,
            ]);
        }
    }

    public function updateNameservers(array $nameservers): void
    {
        // TODO: Maybe there are flags on the class that tell us whether this is possible to even add as an option?
        throw new Exception('DigitalOcean does not allow updating nameservers via the API.');
    }

    public function getNameservers(): array
    {
        return [
            'ns1.digitalocean.com',
            'ns2.digitalocean.com',
            'ns3.digitalocean.com',
        ];
    }

    public function records(): Collection
    {
        $result = $this->client()->get("domains/{$this->domain}/records", [
            'per_page' => 200,
        ])->json();

        return collect($result['domain_records'])
            ->map(fn ($record) => new Record(
                name: $record['name'],
                type: RecordType::from(strtoupper($record['type'])),
                value: $record['data'],
                ttl: $record['ttl'],
                priority: $record['priority'] ?? null,
                tag: $record['tag'] ?? null,
                weight: $record['weight'] ?? null,
                port: $record['port'] ?? null,
                flags: $record['flags'] ?? null,
            ));
    }

    public function domains(): Collection
    {
        $result = $this->client()->get('domains', [
            'per_page' => 200,
        ])->json();

        return collect($result['domains'])->pluck('name');
    }

    public function addRecord(Record $record): void
    {
        $this->client()->post("domains/{$this->domain}/records", [
            'type' => $record->type->value,
            'name' => $record->name,
            'data' => $record->value,
            'ttl'  => $record->ttl,
        ]);
    }

    public function updateRecord(Record $record): void
    {
        $currentRecord = $this->getRecord($record);

        $this->client()->put("domains/{$this->domain}/records/{$currentRecord['id']}", [
            'type' => $record->type->value,
            'name' => $record->name,
            'data' => $record->value,
            'ttl'  => $record->ttl,
        ]);
    }

    public function prepareValue(Record $record): string
    {
        if ($record->type === RecordType::CNAME) {
            return $this->withTrailingDot($record->value);
        }

        return $record->value;
    }

    /** @return array<string, mixed>|null */
    public function getRecord(Record $record): ?array
    {
        $host = Domain::getFullDomain($record->name === '@' ? '' : $record->name, $this->domain);

        $records = $this->client()->get("domains/{$this->domain}/records", [
            'type' => $record->type->value,
            'name' => $host,
        ])->json();

        return $records['domain_records'][0] ?? null;
    }

    public function addNewCredentials(): array
    {
        info('You can create a DigitalOcean API token here:');
        info('https://cloud.digitalocean.com/account/api/tokens');

        $token = password('Your DigitalOcean API token');

        return ['token' => $token];
    }

    public function credentialsAreValid(): bool
    {
        try {
            $this->client()->get('account')->throw();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /** @return array<string, mixed>|null */
    protected function getExistingDomain(): ?array
    {
        try {
            $result = $this->client()->get("domains/{$this->domain}")->throw()->json();
        } catch (RequestException $e) {
            if ($e->response->status() === 404) {
                return null;
            }

            throw $e;
        }

        return $result['domain'];
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl(self::getApiBaseUrl())
            ->withToken($this->credentials['token'])
            ->acceptJson()
            ->asJson();
    }
}
