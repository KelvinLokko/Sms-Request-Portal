<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\SmsRequestStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SmsRequest;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $from ??= now()->subMonths(11)->startOfMonth();
        $to ??= now()->endOfMonth();

        return [
            'summary' => $this->summary(),
            'monthly' => [
                'revenue' => $this->monthlyRevenue($from, $to),
                'requests' => $this->monthlyRequestVolume($from, $to),
                'sms_volume' => $this->monthlySmsVolume($from, $to),
            ],
            'growth' => $this->monthlyGrowth($from, $to),
            'avg_turnaround_hours' => $this->averageTurnaroundHours($from, $to),
            'range' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    public function summary(): array
    {
        $revenuePesewas = (int) Invoice::query()
            ->where('status', InvoiceStatus::Paid)
            ->sum('total_pesewas');

        $smsVolume = (int) SmsRequest::query()
            ->whereIn('status', [
                SmsRequestStatus::Paid,
                SmsRequestStatus::AwaitingFulfilment,
                SmsRequestStatus::Fulfilled,
            ])
            ->sum(DB::raw('COALESCE(billable_recipients, 0) * COALESCE(pages, 1)'));

        return [
            'pending_review' => SmsRequest::query()
                ->whereIn('status', [
                    SmsRequestStatus::Submitted,
                    SmsRequestStatus::UnderReview,
                ])
                ->count(),
            'awaiting_fulfilment' => SmsRequest::query()
                ->whereIn('status', [
                    SmsRequestStatus::Paid,
                    SmsRequestStatus::AwaitingFulfilment,
                ])
                ->count(),
            'pending_payments' => Payment::query()->where('status', 'pending')->count(),
            'fulfilled' => SmsRequest::query()->where('status', SmsRequestStatus::Fulfilled)->count(),
            'revenue_pesewas' => $revenuePesewas,
            'revenue' => Money::format($revenuePesewas),
            'sms_volume' => $smsVolume,
        ];
    }

    /**
     * @return list<array{month: string, label: string, value: int, formatted: string}>
     */
    public function monthlyRevenue(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = Invoice::query()
            ->selectRaw("{$this->monthExpression('paid_at')} as month, SUM(total_pesewas) as total")
            ->where('status', InvoiceStatus::Paid)
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from, $to])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $this->fillMonths($from, $to, $rows->all(), fn (int $v) => Money::format($v));
    }

    /**
     * @return list<array{month: string, label: string, value: int, formatted: string}>
     */
    public function monthlyRequestVolume(CarbonInterface $from, CarbonInterface $to): array
    {
        $rows = SmsRequest::query()
            ->selectRaw("{$this->monthExpression('submitted_at')} as month, COUNT(*) as total")
            ->whereNotNull('submitted_at')
            ->whereBetween('submitted_at', [$from, $to])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $this->fillMonths($from, $to, $rows->all(), fn (int $v) => (string) $v);
    }

    /**
     * @return list<array{month: string, label: string, value: int, formatted: string}>
     */
    public function monthlySmsVolume(CarbonInterface $from, CarbonInterface $to): array
    {
        $dateColumn = 'COALESCE(fulfilled_at, submitted_at)';
        $rows = SmsRequest::query()
            ->selectRaw("{$this->monthExpression($dateColumn)} as month, SUM(COALESCE(billable_recipients, 0) * COALESCE(pages, 1)) as total")
            ->whereIn('status', [
                SmsRequestStatus::Paid,
                SmsRequestStatus::AwaitingFulfilment,
                SmsRequestStatus::Fulfilled,
                SmsRequestStatus::Invoiced,
            ])
            ->where(function ($q) use ($from, $to): void {
                $q->whereBetween('fulfilled_at', [$from, $to])
                    ->orWhere(function ($inner) use ($from, $to): void {
                        $inner->whereNull('fulfilled_at')
                            ->whereBetween('submitted_at', [$from, $to]);
                    });
            })
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $this->fillMonths($from, $to, $rows->all(), fn (int $v) => number_format($v));
    }

    /**
     * @return list<array{month: string, label: string, value: float|null, formatted: string}>
     */
    public function monthlyGrowth(CarbonInterface $from, CarbonInterface $to): array
    {
        $volume = $this->monthlyRequestVolume($from, $to);
        $growth = [];
        $previous = null;

        foreach ($volume as $row) {
            $pct = null;
            if ($previous !== null && $previous > 0) {
                $pct = round((($row['value'] - $previous) / $previous) * 100, 1);
            } elseif ($previous === 0 && $row['value'] > 0) {
                $pct = 100.0;
            }

            $growth[] = [
                'month' => $row['month'],
                'label' => $row['label'],
                'value' => $pct,
                'formatted' => $pct === null ? '—' : $pct.'%',
            ];
            $previous = $row['value'];
        }

        return $growth;
    }

    public function averageTurnaroundHours(CarbonInterface $from, CarbonInterface $to): ?float
    {
        $diff = $this->hourDifferenceExpression('submitted_at', 'fulfilled_at');

        $avg = SmsRequest::query()
            ->where('status', SmsRequestStatus::Fulfilled)
            ->whereNotNull('submitted_at')
            ->whereNotNull('fulfilled_at')
            ->whereBetween('fulfilled_at', [$from, $to])
            ->selectRaw("AVG({$diff}) as avg_hours")
            ->value('avg_hours');

        return $avg === null ? null : round((float) $avg, 1);
    }

    private function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    private function hourDifferenceExpression(string $start, string $end): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "(julianday({$end}) - julianday({$start})) * 24",
            default => "TIMESTAMPDIFF(HOUR, {$start}, {$end})",
        };
    }

    /**
     * @param  iterable<int, object{month?: string, total?: string|int|null}>  $rows
     * @param  callable(int): string  $formatter
     * @return list<array{month: string, label: string, value: int, formatted: string}>
     */
    private function fillMonths(CarbonInterface $from, CarbonInterface $to, iterable $rows, callable $formatter): array
    {
        $map = [];
        foreach ($rows as $row) {
            $month = (string) ($row->month ?? '');
            if ($month === '') {
                continue;
            }
            $map[$month] = (int) ($row->total ?? 0);
        }

        $cursor = $from->toMutable()->startOfMonth();
        $end = $to->toMutable()->startOfMonth();
        $filled = [];

        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m');
            $value = $map[$key] ?? 0;
            $filled[] = [
                'month' => $key,
                'label' => $cursor->format('M Y'),
                'value' => $value,
                'formatted' => $formatter($value),
            ];
            $cursor->addMonth();
        }

        return $filled;
    }
}
