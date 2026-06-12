<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportStorageService
{
    public function disk(): string
    {
        return (string) config('exports.disk', 'local');
    }

    public function put(string $path, string $contents): void
    {
        Storage::disk($this->disk())->put($path, $contents);
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->disk())->exists($path);
    }

    public function get(string $path): string
    {
        return Storage::disk($this->disk())->get($path);
    }

    public function download(string $path, ?string $filename = null): StreamedResponse
    {
        return Storage::disk($this->disk())->download($path, $filename);
    }
}
