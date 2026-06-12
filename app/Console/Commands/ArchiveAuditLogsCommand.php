<?php

namespace App\Console\Commands;

use App\Services\AuditRetentionService;
use Illuminate\Console\Command;

class ArchiveAuditLogsCommand extends Command
{
    protected $signature = 'audit:archive';

    protected $description = 'Archive and purge audit/activity logs older than retention period';

    public function handle(AuditRetentionService $retention): int
    {
        $result = $retention->archiveExpired();

        $this->info('Archived audit logs: ' . ($result['audit_logs'] ?? 0));
        $this->info('Archived activity logs: ' . ($result['activity_logs'] ?? 0));

        return self::SUCCESS;
    }
}
