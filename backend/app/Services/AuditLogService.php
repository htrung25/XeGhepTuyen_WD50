<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'otp',
        'secret',
        'api_key',
    ];

    /**
     * Write an audit log entry for admin actions.
     */
    public function log(
        string $action,
        ?Model $model = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?User $actor = null,
    ): ?AuditLog {
        try {
            return AuditLog::create([
                'user_id' => $actor?->getKey() ?? Auth::id(),
                'action' => $action,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model ? $model->getKey() : null,
                'description' => $description,
                'old_values' => $this->redactSensitiveValues($oldValues),
                'new_values' => $this->redactSensitiveValues($newValues),
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Unable to write audit log', [
                'action' => $action,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model?->getKey(),
                'actor_id' => $actor?->getKey() ?? Auth::id(),
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    private function redactSensitiveValues(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach ($values as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                $values[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->redactSensitiveValues($value);
            }
        }

        return $values;
    }
}
