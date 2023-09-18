<?php

declare(strict_types=1);

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class UpdateRecord extends Command
{
    protected $signature = 'records:update';

    protected $description = 'Add or update a DNS record for a domain.';

    public function handle(): void
    {
        $this->call(AddRecord::class);
    }
}
