<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\Record;
use Illuminate\Support\Collection;

interface Provider
{
    public static function apiKey(): string;

    public static function getApiBaseUrl(): string;

    public static function getName(): string;

    /** @param  array<string, string>  $credentials */
    public function setCredentials(array $credentials): static;

    public function setDomain(string $domain): static;

    public function getDomain(): string;

    /** @return Collection<int, Record> */
    public function records(): Collection;

    /** @return Collection<int, string> */
    public function domains(): Collection;

    /** @return array<int, string> */
    public function getNameservers(): array;

    /** @param  array<int, string>  $nameservers */
    public function updateNameservers(array $nameservers): void;

    public function addDomain(): void;

    public function getRecord(Record $record): mixed;

    public function addRecord(Record $record): void;

    public function updateRecord(Record $record): void;

    public function prepareValue(Record $record): string;

    public function addNewCredentials(): array;

    public function credentialsAreValid(): bool;
}
