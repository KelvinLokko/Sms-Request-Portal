<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class ListFilters
{
    /**
     * @return array{status: string|null, from: string|null, to: string|null, q: string|null}
     */
    public static function fromRequest(Request $request): array
    {
        $status = $request->string('status')->toString();
        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();
        $q = $request->string('q')->toString();

        return [
            'status' => $status !== '' ? $status : null,
            'from' => $from !== '' ? $from : null,
            'to' => $to !== '' ? $to : null,
            'q' => $q !== '' ? $q : null,
        ];
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public static function applyDateRange(
        Builder $query,
        ?string $from,
        ?string $to,
        string $column = 'created_at',
    ): Builder {
        if ($from !== null && $from !== '') {
            $query->where($column, '>=', Carbon::parse($from)->startOfDay());
        }

        if ($to !== null && $to !== '') {
            $query->where($column, '<=', Carbon::parse($to)->endOfDay());
        }

        return $query;
    }
}
