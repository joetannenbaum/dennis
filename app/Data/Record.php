<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\RecordType;
use Spatie\LaravelData\Data;

class Record extends Data
{
    public function __construct(
        public RecordType $type,
        public string $name,
        public string $value,
        public int $ttl,
        public ?int $priority = null,
        public ?string $tag = null,
        public ?int $weight = null,
        public ?int $port = null,
        public ?int $flags = null,
        public ?string $comment = null,
    ) {
    }
}
