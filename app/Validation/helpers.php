<?php

declare(strict_types=1);

namespace App\Validation;

use Closure;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\info;

/**
 * @param  array<string, mixed>  $rules
 * @param  array<string, string>  $messages
 */
function rules(array $rules, string $fieldName = 'value', array $messages = []): Closure
{
    return fn ($value) => Validator::make(
        [$fieldName => $value],
        [$fieldName => $rules],
        $messages,
    )->errors()->first();
}

// TODO: This doesn't belong in validation but cool for now.
function miniTask(string $key, string $value, bool $successful = true): void
{
    $successIndicator = $successful ? '✓' : '✗';

    info(" <comment>{$successIndicator}</comment> <info>{$key}</info>: <comment>{$value}</comment>");
}
