<?php

declare(strict_types=1);

namespace App\Commands;

use App\Data\Record;
use App\Enums\RecordType;
use App\Support\Config;
use App\Support\SelectsADomain;
use LaravelZero\Framework\Commands\Command;

use function App\Validation\rules;
use function Laravel\Prompts\error;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class AddRecord extends Command
{
    use SelectsADomain;

    protected $signature = 'records:add';

    protected $description = 'Command description';

    public function handle(Config $config)
    {
        $provider = $this->selectDomain($config);

        $recordType = select(
            label: 'Record Type',
            options: collect(RecordType::cases())->pluck('value'),
        );

        $name = text(
            label: 'Name',
            required: true,
            hint: 'The host name, alias, or service being defined by the record. @ for the root domain.',
        );

        $value = text(
            label: 'Value',
            required: true,
        );

        $ttl = text(
            label: 'TTL',
            hint: 'The time to live for the record, in seconds.',
            required: true,
            default: '3600',
            validate: rules(
                ['integer', 'min:0'],
                'TTL',
                [
                    'integer' => 'TTL must be an integer.',
                    'min'     => 'TTL must be greater than 0.',
                ],
            ),
        );

        $result = $provider->addRecord(
            new Record(
                type: RecordType::from($recordType),
                name: $name,
                value: $value,
                ttl: (int) $ttl,
            ),
        );

        if ($result) {
            info('Record added successfully.');
        } else {
            error('There was an error adding the record.');
        }
    }
}
