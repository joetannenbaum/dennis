<?php

declare(strict_types=1);

namespace App\Commands;

use App\Data\Record;
use App\Support\SelectsADomain;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\table;

class ListRecords extends Command
{
    use SelectsADomain;

    protected $signature = 'records:list';

    protected $description = 'List DNS records for a domain.';

    public function handle(): void
    {
        intro('List Records');

        $provider = $this->selectDomain();

        $records = $provider->records()->map(fn (Record $record) => [
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
            $records->toArray(),
        );
    }
}
