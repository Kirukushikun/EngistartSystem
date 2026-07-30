<?php

namespace App\Livewire\Shared;

use App\Livewire\Concerns\BuildsRequestCardData;
use App\Models\ProjectRequest;
use App\Support\ProjectTimelineCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProjectCalendarPage extends Component
{
    use BuildsRequestCardData;

    public string $farmFilter = 'all';

    public ?string $selectedId = null;

    public function getFarmOptionsProperty(): array
    {
        return ProjectRequest::query()
            ->whereNotNull('farm_name')
            ->distinct()
            ->orderBy('farm_name')
            ->pluck('farm_name', 'farm_name')
            ->all();
    }

    public function getScopeNoteProperty(): string
    {
        $user = Auth::user();

        return match ($user?->role) {
            'farm_manager' => 'Showing the requests you submitted, plotted from project start to completion.',
            'engineer' => 'Showing the projects assigned to you for execution.',
            default => 'Showing every request in the system, plotted from project start to completion.',
        };
    }

    public function getRowsProperty(): Collection
    {
        $user = Auth::user();

        return ProjectRequest::query()
            ->with(['requestor', 'assignedEngineer'])
            ->whereNotIn('current_status', ['withdrawn', 'rejected'])
            ->when($user, function ($query) use ($user) {
                if ($user->role === 'farm_manager') {
                    $query->where('requestor_id', $user->id);

                    return;
                }

                if ($user->role === 'engineer') {
                    $query->where('assigned_engineer_id', $user->id);
                }
            })
            ->when($this->farmFilter !== 'all', fn ($query) => $query->where('farm_name', $this->farmFilter))
            ->get()
            ->map(function (ProjectRequest $request): ?array {
                $isProjected = ! $request->project_start_date || ! $request->project_completion_date;

                if ($isProjected) {
                    if (! $request->budget_category) {
                        return null;
                    }

                    $timeline = ProjectTimelineCalculator::forCategory($request->budget_category, $request->submitted_at ?? $request->created_at);

                    if (! $timeline) {
                        return null;
                    }

                    $start = $timeline['start_date'];
                    $completion = $timeline['completion_date'];
                } else {
                    $start = Carbon::parse($request->project_start_date);
                    $completion = Carbon::parse($request->project_completion_date);
                }

                return [
                    'id' => $request->request_number,
                    'title' => $request->title,
                    'farm' => $request->farm_name ?? 'Farm not yet specified',
                    'by' => $request->requestor?->name ?? 'Unknown requester',
                    'status' => $request->current_status,
                    'statusLabel' => ProjectRequest::statusLabel($request->current_status),
                    'budgetCategory' => $this->budgetCategoryLabel($request->budget_category),
                    'assignedEngineer' => $request->assignedEngineer?->name ?? '—',
                    'purpose' => $request->purpose,
                    'isProjected' => $isProjected,
                    'startDate' => $start,
                    'completionDate' => $completion,
                ];
            })
            ->filter()
            ->sortBy('startDate')
            ->values();
    }

    public function getTimelineProperty(): ?array
    {
        $rows = $this->rows;

        if ($rows->isEmpty()) {
            return null;
        }

        $rangeStart = $rows->min('startDate')->copy()->startOfMonth();
        $rangeEnd = $rows->max('completionDate')->copy()->endOfMonth();
        $totalDays = max(1, $rangeStart->diffInDays($rangeEnd));

        $leftPercent = fn (Carbon $date) => max(0, $rangeStart->diffInDays($date)) / $totalDays * 100;
        $widthPercent = fn (Carbon $start, Carbon $end) => max(0.6, $start->diffInDays($end) / $totalDays * 100);

        $months = [];
        $cursor = $rangeStart->copy();
        while ($cursor->lte($rangeEnd)) {
            $monthEnd = $cursor->copy()->endOfMonth()->min($rangeEnd);
            $months[] = [
                'label' => $cursor->format('M Y'),
                'left' => $leftPercent($cursor),
                'width' => $widthPercent($cursor, $monthEnd->copy()->addDay()),
            ];
            $cursor->addMonthNoOverflow()->startOfMonth();
        }

        $today = Carbon::today();
        $todayLeft = $today->between($rangeStart, $rangeEnd) ? $leftPercent($today) : null;

        $farms = [];
        foreach ($this->rows->groupBy('farm') as $farm => $farmRows) {
            $farms[] = [
                'farm' => $farm,
                'rows' => $farmRows->map(function (array $row) use ($leftPercent, $widthPercent) {
                    $row['left'] = $leftPercent($row['startDate']);
                    $row['width'] = $widthPercent($row['startDate'], $row['completionDate']);

                    return $row;
                })->values(),
            ];
        }

        return [
            'months' => $months,
            'todayLeft' => $todayLeft,
            'farms' => $farms,
        ];
    }

    public function render()
    {
        return view('livewire.shared.project-calendar-page')
            ->layout('layouts.app', [
                'title' => 'Project Calendar | EngiStart',
                'header' => 'Project Calendar',
                'subheader' => 'A timeline view of project requests from start to completion.',
            ]);
    }
}
