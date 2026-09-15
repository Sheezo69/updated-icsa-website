<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AdminActivityLogger
{
    private const SENSITIVE_KEYS = [
        'password', 'password_hash', 'current_password', 'new_password',
        'new_password_confirmation', 'token', 'secret', 'remember_token',
    ];

    public function record(
        Request $request,
        string $action,
        string $section,
        string $description,
        ?string $subjectType = null,
        string|int|null $subjectId = null,
        ?string $subjectLabel = null,
        ?array $before = null,
        ?array $after = null,
        ?Admin $actor = null,
    ): ?AdminActivityLog {
        try {
            if (! Schema::hasTable('admin_activity_logs')) {
                return null;
            }

            $actor ??= $request->attributes->get('currentAdmin');
            if (! $actor instanceof Admin) {
                $actorId = (int) $request->session()->get('admin_id');
                $actor = $actorId > 0 ? Admin::query()->find($actorId) : null;
            }
            if (! $actor instanceof Admin) {
                return null;
            }

            return AdminActivityLog::query()->create([
                'admin_id' => $actor->id,
                'actor_name' => $actor->username,
                'actor_role' => $actor->role,
                'action' => Str::limit($action, 60, ''),
                'section' => Str::limit($section, 40, ''),
                'subject_type' => $subjectType ? Str::limit($subjectType, 100, '') : null,
                'subject_id' => $subjectId !== null ? Str::limit((string) $subjectId, 120, '') : null,
                'subject_label' => $subjectLabel ? Str::limit($subjectLabel, 255, '') : null,
                'description' => Str::limit($description, 500, ''),
                'before_values' => $this->sanitize($before),
                'after_values' => $this->sanitize($after),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach ($values as $key => $value) {
            $normalized = Str::lower((string) $key);
            if (in_array($normalized, self::SENSITIVE_KEYS, true)
                || str_contains($normalized, 'password')
                || str_contains($normalized, 'token')
                || str_contains($normalized, 'secret')) {
                unset($values[$key]);

                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->sanitize($value);
            }
        }

        return $values;
    }
}
