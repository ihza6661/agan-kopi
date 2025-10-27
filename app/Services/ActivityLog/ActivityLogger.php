<?php

namespace App\Services\ActivityLog;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger implements ActivityLoggerInterface
{
    public function log(string $activity, ?string $description = null, ?array $context = null): void
    {
        try {
            $desc = $description;
            if ($context !== null) {
                $json = json_encode($context, JSON_UNESCAPED_UNICODE);
                $desc = $desc ? ($desc . ' | ' . $json) : $json;
            }

            ActivityLog::query()->create([
                'user_id' => Auth::id(),
                'activity' => mb_substr($activity, 0, 255),
                'description' => $desc,
                'ip_address' => request()->ip(),
                'user_agent' => (string) request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
        }
    }
}
