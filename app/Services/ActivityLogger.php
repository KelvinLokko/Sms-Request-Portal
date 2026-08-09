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
            'company_id' => $this->resolveCompanyId($actor, $subject, $properties),
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
     * @param  array<string, mixed>  $properties
     */
    private function resolveCompanyId(?User $actor, ?Model $subject, array $properties): ?int
    {
        $fromActor = $actor?->currentCompanyId();

        if ($fromActor !== null) {
            return $fromActor;
        }

        if (array_key_exists('company_id', $properties)) {
            return $this->nullableInt($properties['company_id']);
        }

        if ($subject !== null && array_key_exists('company_id', $subject->getAttributes())) {
            return $this->nullableInt($subject->getAttributes()['company_id']);
        }

        return null;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
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
