<?php

use App\Data\Record;
use App\DnsProviders\DigitalOcean;
use App\Enums\RecordType;
use Illuminate\Support\Facades\Http;
use Laravel\Prompts\Prompt;

beforeEach(function () {
    Prompt::fake();
    Http::preventStrayRequests();

    $this->provider = app(DigitalOcean::class);

    $this->provider->setCredentials(['token' => 'mytoken'])
        ->setDomain('example.com');
});

it('can add a domain if it does not exist', function () {
    Http::fake([
        'domains/example.com' => Http::response(null, 404),
        'domains'             => Http::response([
            'domain' => [
                'name' => 'example.com',
            ],
        ]),
    ]);

    $this->provider->addDomain();

    assertRequestWasSent('GET', 'domains/example.com', []);

    assertRequestWasSent('POST', 'domains', [
        'name' => 'example.com',
    ]);
});

it('will not re-add add a domain if it does exist', function () {
    Http::fake([
        'domains/example.com' => Http::response([
            'domain' => [
                'name' => 'example.com',
            ],
        ]),
    ]);

    $this->provider->addDomain();

    assertRequestWasSent('GET', 'domains/example.com', []);
});

it('can list records', function () {
    Http::fake([
        'domains/example.com/records?per_page=200' => Http::response([
            'domain_records' => [
                [
                    'name'     => 'example.com',
                    'type'     => 'A',
                    'data'     => '127.0.0.1',
                    'priority' => null,
                    'port'     => null,
                    'ttl'      => 1800,
                ],
            ],
        ]),

    ]);

    $records = $this->provider->listRecords();

    assertRequestWasSent('GET', 'domains/example.com/records', ['per_page' => 200]);

    expect($records)->toHaveCount(1);

    $record = $records->first();

    expect($record)->toBeInstanceOf(Record::class);
    expect($record->name)->toBe('example.com');
    expect($record->type)->toBe(RecordType::A);
    expect($record->value)->toBe('127.0.0.1');
    expect($record->ttl)->toBe(1800);
    expect($record->priority)->toBeNull();
    expect($record->tag)->toBeNull();
    expect($record->weight)->toBeNull();
    expect($record->port)->toBeNull();
    expect($record->flags)->toBeNull();
});

it('can add a non-existing record', function () {
    Http::fake([
        'domains/example.com/records?type=CNAME&name=yup.example.com' => Http::response([
            'domain_records' => [],
        ]),
        'domains/example.com/records' => Http::response([
            'domain_records' => [
                [
                    'name'     => 'yup',
                    'type'     => 'CNAME',
                    'data'     => 'anotherone.biz',
                    'priority' => null,
                    'port'     => null,
                    'ttl'      => 1800,
                ],
            ],
        ]),
    ]);

    $this->provider->addRecord(new Record(
        name: 'yup',
        type: RecordType::CNAME,
        value: 'anotherone.biz',
        ttl: 1800,
    ));

    assertRequestWasSent('GET', 'domains/example.com/records', [
        'type' => 'CNAME',
        'name' => 'yup.example.com',
    ]);

    assertRequestWasSent('POST', 'domains/example.com/records', [
        'type' => 'CNAME',
        'name' => 'yup',
        'data' => 'anotherone.biz',
        'ttl'  => 1800,
    ]);
});

it('can update an existing record', function () {
    Http::fake([
        'domains/example.com/records?type=CNAME&name=yup.example.com' => Http::response([
            'domain_records' => [
                [
                    'id'       => '123',
                    'name'     => 'yup',
                    'type'     => 'CNAME',
                    'data'     => 'firstone.biz',
                    'priority' => null,
                    'port'     => null,
                    'ttl'      => 1800,
                ],
            ],
        ]),
        'domains/example.com/records/123' => Http::response([
            'domain_records' => [
                [
                    'name'     => 'yup',
                    'type'     => 'CNAME',
                    'data'     => 'anotherone.biz',
                    'priority' => null,
                    'port'     => null,
                    'ttl'      => 1800,
                ],
            ],
        ]),
    ]);

    $this->provider->addRecord(new Record(
        name: 'yup',
        type: RecordType::CNAME,
        value: 'anotherone.biz',
        ttl: 1800,
    ));

    assertRequestWasSent('GET', 'domains/example.com/records', [
        'type' => 'CNAME',
        'name' => 'yup.example.com',
    ]);

    assertRequestWasSent('PUT', 'domains/example.com/records/123', [
        'type' => 'CNAME',
        'name' => 'yup',
        'data' => 'anotherone.biz',
        'ttl'  => 1800,
    ]);
});
