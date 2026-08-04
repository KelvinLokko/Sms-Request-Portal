<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(
        string $action,
        ?Model $subject = null,
        array $properties = [],
        ?User $actor = null,
        ?Request $request = null,
    ): ActivityLog {
        $actor ??= Auth::user();
        $request ??= request();

        return ActivityLog::query()->create([
            'user_id' => $actor?->id,
            'company_id' => $actor?->currentCompanyId()
                ?? (array_key_exists('company_id', $subject?->getAttributes() ?? [])
                    ? (int) $subject->getAttributes()['company_id']
                    : null),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties === [] ? null : $properties,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Log a privileged mutation with reconstructable before/after state.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<string, mixed>  $properties
     */
    public function logChange(
        string $action,
        Model $subject,
        array $before,
        array $after,
        array $properties = [],
        ?User $actor = null,
        ?Request $request = null,
    ): ActivityLog {
        return $this->log($action, $subject, [
            ...$properties,
            'before' => $before,
            'after' => $after,
        ], $actor, $request);
    }
}
