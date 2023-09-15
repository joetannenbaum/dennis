<?php

declare(strict_types=1);

namespace App\DnsProviders;

use App\Data\Record;
use App\Enums\RecordType;
use App\Support\Domain;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use function Laravel\Prompts\info;
use function Laravel\Prompts\password;

class DigitalOcean extends AbstractDnsProvider
{
    protected static function getApiBaseUrl(): string
    {
        return 'https://api.digitalocean.com/v2/';
    }

    public function addDomain()
    {
        if ($this->getExistingDomain() === null) {
            $this->client()->post('domains', [
                'name' => $this->domain,
            ]);
        }
    }

    public function updateNameservers(array $nameservers)
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

    public function listRecords(): Collection
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

    public function listDomains(): array
    {
        $result = $this->client()->get('domains', [
            'per_page' => 200,
        ])->json();

        return collect($result['domains'])->pluck('name')->toArray();
    }

    protected function getExistingDomain(): ?array
    {
        $result = $this->client()->get("domains/{$this->domain}")->throw()->json();

        return $result['domain'] ?? null;
    }

    protected function addProviderRecord(Record $record): void
    {
        $this->client()->post("domains/{$this->domain}/records", [
            'type' => $record->type->value,
            'name' => $record->name,
            'data' => $record->value,
            'ttl'  => $record->ttl,
        ]);
    }

    protected function updateProviderRecord(Record $record): void
    {
        $currentRecord = $this->getRecord($record);

        $this->client()->put("domains/{$this->domain}/records/{$currentRecord['id']}", [
            'type' => $record->type->value,
            'name' => $record->name,
            'data' => $record->value,
            'ttl'  => $record->ttl,
        ]);
    }

    protected function prepValue(Record $record): string
    {
        if ($record->type === RecordType::CNAME) {
            return $this->withTrailingDot($record->value);
        }

        return $record->value;
    }

    protected function addNewCredentials(): array
    {
        info('You can create a DigitalOcean API token here:');
        info('https://cloud.digitalocean.com/account/api/tokens');

        $token = password('Your DigitalOcean API token');

        return ['token' => $token];
    }

    protected function credentialsAreValid(): bool
    {
        try {
            $this->client()->get('account')->throw();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl(self::getApiBaseUrl())
            ->withToken($this->credentials['token'])
            ->acceptJson()
            ->asJson();
    }

    protected function getDefaultNewAccountName(array $credentials): ?string
    {
        $result = $this->client($credentials)->get('account')->json();

        $teamName = Arr::get($result, 'account.team.name');

        return $teamName === 'My Team' ? null : Str::slug($teamName);
    }

    protected function getRecord(Record $record): ?array
    {
        $host = Domain::getFullDomain($record->name === '@' ? '' : $record->name, $this->domain);

        $records = $this->client()->get("domains/{$this->domain}/records", [
            'type' => $record->type->value,
            'name' => $host,
        ])->json();

        return $records['domain_records'][0] ?? null;
    }
}
