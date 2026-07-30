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

    public static function forCategory(string $category, Carbon $from): ?array
    {
        $config = config('project_timelines.' . $category);

        if (! $config) {
            return null;
        }

        $startDate = self::addBusinessDays($from->copy(), $config['start_offset_days']);

        return [
            'start_date' => $startDate,
            'completion_date' => self::addBusinessDays($startDate->copy(), $config['completion_offset_days']),
        ];
    }

    private static function addBusinessDays(Carbon $date, int $days): Carbon
    {
        $result = $date->copy();
        $added = 0;

        if (! $result->isWeekend()) {
            $added++;
        }

        while ($added < $days) {
            $result->addDay();

            if (! $result->isWeekend()) {
                $added++;
            }
        }

        return $result;
    }
}
