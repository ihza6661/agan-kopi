<?php

namespace App\Services\ActivityLog;

interface ActivityLoggerInterface
{
    public function log(string $activity, ?string $description = null, ?array $context = null): void;
}
