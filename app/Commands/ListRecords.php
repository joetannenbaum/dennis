<?php

declare(strict_types=1);

namespace App\Commands;

use App\Data\Record;
use App\Support\Config;
use App\Support\SelectsADomain;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

class ListRecords extends Command
{
    use SelectsADomain;

    protected $signature = 'records:list';

    protected $description = 'Command description';

    public function handle(Config $config)
    {
        $provider = $this->selectDomain($config);

        $records = $provider->listRecords()->map(fn (Record $record) => [
            $record->type->value,
            $record->name,
            wordwrap(
                string: $record->value,
                width: 45,
                cut_long_words: true,
            ),
            $record->ttl,
            $record->priority,
            $record->comment,
        ]);

        if ($records->isEmpty()) {
            info('No records found.');

            return;
        }

        table(
            ['Type', 'Name', 'Value', 'TTL', 'Priority', 'Comment'],
            $records,
        );
    }
}
