<?php

namespace App\Support;

use Carbon\Carbon;

final class ProjectTimelineCalculator
{
    public static function categories(): array
    {
        return collect(config('project_timelines'))
            ->map(fn (array $config) => $config['label'])
            ->all();
    }

    public static function forCategory(string $category, ?Carbon $from = null): ?array
    {
        $config = config('project_timelines.' . $category);

        if (! $config) {
            return null;
        }

        $from = ($from ?? Carbon::today())->copy();

        return [
            'start_date' => $from->copy()->addDays($config['start_offset_days']),
            'completion_date' => $from->copy()->addDays($config['completion_offset_days']),
        ];
    }
}
