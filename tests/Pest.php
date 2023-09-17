<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(Tests\TestCase::class)->in('Feature');

function isUrl(Request $request, string $path): bool
{
    if (str_contains($path, 'https://')) {
        return $request->url() === $path;
    }

    return Str::endsWith(parse_url($request->url(), PHP_URL_PATH), $path);
}

function assertRequestWasSent(string $method, string $url, array $data): void
{
    Http::assertSent(
        fn (Request $request) => isUrl($request, $url)
            && strtoupper($request->method()) === strtoupper($method)
            && $request->data() === $data
    );
}
