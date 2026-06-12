<?php

namespace App\Jobs;

use App\Models\DataMigration;
use App\Services\DataMigrationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunDataMigrationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $migrationId)
    {
    }

    public function handle(DataMigrationService $service): void
    {
        $migration = DataMigration::withoutGlobalScopes()->find($this->migrationId);

        if (! $migration || $migration->status !== 'pending') {
            return;
        }

        $service->process($migration);
    }
}
