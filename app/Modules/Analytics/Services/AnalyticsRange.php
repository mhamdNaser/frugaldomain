<?php

namespace App\Modules\Analytics\Services;

use Illuminate\Support\Carbon;

/**
 * The reporting window selected in the admin UI (?range=7d|30d|90d|12m),
 * plus the equally long window right before it for "% change" figures.
 */
class AnalyticsRange
{
    public const RANGES = ['7d', '30d', '90d', '12m'];

    public function __construct(
        public readonly string $key,
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly Carbon $previousStart,
        public readonly Carbon $previousEnd,
    ) {
    }

    public static function fromKey(?string $key, ?Carbon $now = null): self
    {
        $key = in_array($key, self::RANGES, true) ? $key : '30d';
        $now = ($now ?? Carbon::now())->copy();

        $start = match ($key) {
            '7d' => $now->copy()->startOfDay()->subDays(6),
            '90d' => $now->copy()->startOfDay()->subDays(89),
            '12m' => $now->copy()->startOfMonth()->subMonths(11),
            default => $now->copy()->startOfDay()->subDays(29),
        };

        $seconds = $now->getTimestamp() - $start->getTimestamp();
        $previousEnd = $start->copy();
        $previousStart = $key === '12m'
            ? $start->copy()->subMonths(12)
            : $start->copy()->subSeconds($seconds);

        return new self($key, $start, $now, $previousStart, $previousEnd);
    }

    public function toArray(): array
    {
        return [
            'range' => $this->key,
            'from' => $this->start->toIso8601String(),
            'to' => $this->end->toIso8601String(),
            'previous_from' => $this->previousStart->toIso8601String(),
            'previous_to' => $this->previousEnd->toIso8601String(),
        ];
    }
}
