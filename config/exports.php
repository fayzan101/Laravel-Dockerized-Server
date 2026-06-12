<?php

return [
    /*
    | Disk used for GDPR exports, audit archives, and migration snapshots.
    | Set EXPORTS_DISK=s3 in production when AWS credentials are configured.
    */
    'disk' => env('EXPORTS_DISK', 'local'),
];
