<?php

declare(strict_types=1);

namespace App\DnsProviders;

use App\Data\Record;
use App\Enums\RecordType;
use App\Support\Domain;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

use function Laravel\Prompts\info;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;

class Cloudflare extends AbstractDnsProvider
{
    protected string $zoneId;

    protected static function getApiBaseUrl(): string
    {
        return 'https://api.cloudflare.com/client/v4/';
    }

    public function addDomain()
    {
        if ($this->getZoneByDomain() !== null) {
            // We already have the domain, so we don't need to do anything
            return;
        }

        $accountId = $this->getAccountId();

        $this->client()->post('zones', [
            'name'    => $this->domain,
            'account' => [
                'id' => $accountId,
            ],
        ])->throw();
    }

    public function updateNameservers(array $nameservers)
    {
        // TODO: Is this true? Seems a bit nebulous via the docs
        throw new Exception('Cloudflare does not allow updating nameservers via the API.');
    }

    public function getNameservers(): array
    {
        $zone = $this->getZoneByDomain();

        if (!$zone) {
            throw new Exception("Zone not found for {$this->domain}");
        }

        return $zone['name_servers'];
    }

    public function listRecords(): Collection
    {
        $response = $this->client()->get(
            "zones/{$this->getZoneId()}/dns_records",
            ['per_page' => 1000],
        )->json();

        return collect($response['result'])->map(
            fn ($record) => new Record(
                name: $record['name'],
                type: RecordType::from(strtoupper($record['type'])),
                value: $record['content'],
                ttl: $record['ttl'],
                priority: $record['priority'] ?? null,
                comment: $record['comment'] ?? null,
                tag: isset($record['tags']) ? implode(', ', $record['tags']) : null,
            ),
        );
    }

    public function listDomains(): array
    {
        $response = $this->client()->get('zones', ['per_page' => 200])->json();

        return collect($response['result'])->pluck('name')->toArray();
    }

    protected function getZoneByDomain(): ?array
    {
        return $this->client()->get('zones', [
            'name' => $this->domain,
        ])->throw()->json()['result'][0] ?? null;
    }

    protected function getAccountId(): string
    {
        $accounts = collect($this->client()->get('accounts')->json()['result']);

        if ($accounts->count() === 1) {
            return $accounts->first()['id'];
        }

        return select(
            label: 'Select the Cloudflare account you want to use:',
            options: $accounts->pluck('name', 'id'),
        );
    }

    protected function addProviderRecord(Record $record): void
    {
        $this->client()->post(
            "zones/{$this->getZoneId()}/dns_records",
            [
                'type'    => $record->type->value,
                'name'    => $record->name,
                'content' => $record->value,
                'ttl'     => $record->ttl,
            ],
        );
    }

    protected function updateProviderRecord(Record $record): void
    {
        $currentRecord = $this->getRecord($record);

        $this->client()->put(
            "zones/{$this->getZoneId()}/dns_records/{$currentRecord['id']}",
            [
                'type'    => $record->type->value,
                'name'    => $record->name,
                'content' => $record->value,
                'ttl'     => $record->ttl,
            ],
        );
    }

    protected function addNewCredentials(): array
    {
        info('You can create a Cloudflare API token here:');
        info('https://dash.cloudflare.com/profile/api-tokens');
        info('');
        info('Make sure you give it the following permissions:');
        info('Account.Account Settings (Read), Zone.Zone (Edit), Zone.DNS (Edit)');

        $token = password('Your Cloudflare API token');

        return ['token' => $token];
    }

    protected function credentialsAreValid(): bool
    {
        try {
            $this->client()->get('user/tokens/verify')->throw();

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

    protected function getRecord(Record $record): ?array
    {
        $host = Domain::getFullDomain($record->name === '@' ? '' : $record->name, $this->domain);

        $records = $this->client()->get(
            "zones/{$this->getZoneId()}/dns_records",
            [
                'type' => $record->type->value,
                'name' => $host,
            ],
        )->json();

        return $records['result'][0] ?? null;
    }

    protected function getZoneId()
    {
        if (isset($this->zoneId)) {
            return $this->zoneId;
        }

        $zone = $this->client()->get('zones', [
            'name' => $this->domain,
        ])->json();

        $this->zoneId = $zone['result'][0]['id'];

        return $this->zoneId;
    }
}
