<?php

declare(strict_types=1);

namespace App\DnsProviders;

use App\Data\Record;
use App\Enums\RecordType;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\password;

class GoDaddy extends AbstractDnsProvider
{
    protected static function getApiBaseUrl(): string
    {
        return 'https://api.godaddy.com/v1/';
    }

    public function addDomain()
    {
        // TODO: Maybe there are flags on the class that tell us whether this is possible to even add as an option?
        throw new Exception('GoDaddy does not allow adding DNS hosting domains via the API.');
    }

    public function updateNameservers(array $nameservers)
    {
        $this->client()->patch("domains/{$this->domain}", [
            'nameServers' => $nameservers,
        ])->json();
    }

    public function getNameservers(): array
    {
        return [
            'ns43.domaincontrol.com',
            'ns44.domaincontrol.com',
        ];
    }

    public function listRecords(): Collection
    {
        try {
            $response = $this->client()->get("domains/{$this->domain}/records", [
                'limit' => 500,
            ])->throw()->json();
        } catch (RequestException $e) {
            error($e->response->json(['message']) ?? 'An error occurred while fetching records.');

            return collect();
        }

        return collect($response)->map(fn ($record) => new Record(
            type: RecordType::from(strtoupper($record['type'])),
            name: $record['name'],
            value: $record['data'],
            ttl: $record['ttl'],
            priority: $record['priority'] ?? null,
            weight: $record['weight'] ?? null,
        ));
    }

    public function listDomains(): Collection
    {
        $result = $this->client()->get('domains', [
            'limit' => 500,
        ])->json();

        return collect($result)
            ->filter(fn ($domain) => $domain['status'] === 'ACTIVE')
            ->pluck('domain');
    }

    protected function addProviderRecord(Record $record): void
    {
        $this->client()->patch("domains/{$this->domain}/records", [
            [
                'data' => $record->value,
                'name' => $record->name,
                'ttl'  => $record->ttl,
                'type' => $record->type->value,
            ],
        ]);
    }

    protected function updateProviderRecord(Record $record): void
    {
        $this->client()->put("domains/{$this->domain}/records/{$record->type->value}/{$record->name}", [
            [
                'data' => $record->value,
                'ttl'  => $record->ttl,
            ],
        ]);
    }

    protected function addNewCredentials(): array
    {
        info('You can retrieve your GoDaddy keys here:');
        info('https://developer.godaddy.com/keys');

        $key = password('Your GoDaddy key');
        $secret = password('Your GoDaddy secret');

        return ['key' => $key, 'secret' => $secret];
    }

    protected function credentialsAreValid(): bool
    {
        try {
            $this->client()->get('domains', ['limit' => 1])->throw()->json();

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    protected function prepValue(Record $record): string
    {
        if ($record->type === RecordType::CNAME) {
            return $this->withTrailingDot($record->value);
        }

        return $record->value;
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl(self::getApiBaseUrl())
            ->withToken(
                "{$this->credentials['key']}:{$this->credentials['secret']}",
                'sso-key',
            )
            ->acceptJson()
            ->asJson();
    }

    protected function getRecord(RecordType $type, string $name): ?string
    {
        return $this->getRecord($type, $name)['data'] ?? null;
    }

    protected function getRecord(Record $record): ?array
    {
        try {
            $response = $this->client()
                ->get("domains/{$this->domain}/records/{$record->type->value}/{$record->name}")
                ->throw()
                ->json();

            return $response[0] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }
}
