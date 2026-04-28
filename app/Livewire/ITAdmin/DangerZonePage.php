<?php

namespace App\Livewire\ITAdmin;

use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use Illuminate\Support\Carbon;
use Livewire\Component;

class DangerZonePage extends Component
{
    public string $wipeMode = 'all';

    public string $wipeFrom = '';

    public string $wipeTo = '';

    public string $wipeYear = '';

    public ?int $wipeCount = null;

    public string $photoMode = 'quarter';

    public string $photoYear = '';

    public string $photoQuarter = '';

    public string $photoFrom = '';

    public string $photoTo = '';

    public ?int $photoCount = null;

    public string $logMode = 'all';

    public string $logFrom = '';

    public string $logTo = '';

    public string $logYear = '';

    public ?int $logCount = null;

    public ?string $confirmingGroup = null;

    public string $confirmInput = '';

    public function updatedWipeMode(): void
    {
        $this->wipeCount = null;
    }

    public function updatedWipeFrom(): void
    {
        $this->wipeCount = null;
    }

    public function updatedWipeTo(): void
    {
        $this->wipeCount = null;
    }

    public function updatedWipeYear(): void
    {
        $this->wipeCount = null;
    }

    public function updatedPhotoMode(): void
    {
        $this->photoCount = null;
    }

    public function updatedPhotoYear(): void
    {
        $this->photoCount = null;

        if ($this->photoYear === '') {
            $this->photoQuarter = '';
        }
    }

    public function updatedPhotoQuarter(): void
    {
        $this->photoCount = null;
    }

    public function updatedPhotoFrom(): void
    {
        $this->photoCount = null;
    }

    public function updatedPhotoTo(): void
    {
        $this->photoCount = null;
    }

    public function updatedLogMode(): void
    {
        $this->logCount = null;
    }

    public function updatedLogFrom(): void
    {
        $this->logCount = null;
    }

    public function updatedLogTo(): void
    {
        $this->logCount = null;
    }

    public function updatedLogYear(): void
    {
        $this->logCount = null;
    }

    public function previewCount(string $group): void
    {
        $count = match ($group) {
            'wipe' => $this->resolveWipeCount(),
            'photo' => $this->resolvePhotoCount(),
            'log' => $this->resolveLogCount(),
            default => null,
        };

        if ($count === null) {
            return;
        }

        $this->{$group . 'Count'} = $count;
    }

    public function openConfirm(string $group): void
    {
        $count = $this->{$group . 'Count'};

        if ($count === null) {
            $this->previewCount($group);
            $count = $this->{$group . 'Count'};
        }

        if ($count === null) {
            return;
        }

        $this->confirmingGroup = $group;
        $this->confirmInput = '';
    }

    public function closeConfirm(): void
    {
        $this->confirmingGroup = null;
        $this->confirmInput = '';
    }

    public function queueAction(): void
    {
        $group = $this->confirmingGroup;

        if (! $group) {
            return;
        }

        $count = $this->{$group . 'Count'} ?? 0;

        if (! $this->confirmationMatchesCount($count)) {
            $this->dispatch('notify', type: 'warn', message: 'Enter the exact preview count to continue.');

            return;
        }

        $message = match ($group) {
            'wipe' => 'Wipe Submissions is not active yet. The preview is ready, but destructive execution still needs backend rules and authorization.',
            'photo' => 'Purge Attachment Photos is not active yet. The preview is ready, but destructive execution still needs backend rules and authorization.',
            'log' => 'Purge Activity Logs is not active yet. The preview is ready, but destructive execution still needs backend rules and authorization.',
            default => 'Action is not available.',
        };

        $this->dispatch('notify', type: 'warn', message: $message);
        $this->closeConfirm();
    }

    public function getConfirmCountProperty(): int
    {
        return $this->confirmingGroup ? ($this->{$this->confirmingGroup . 'Count'} ?? 0) : 0;
    }

    public function getCanConfirmProperty(): bool
    {
        return $this->confirmationMatchesCount($this->confirmCount);
    }

    public function getQuarterDisabledProperty(): bool
    {
        return $this->photoYear === '';
    }

    public function getAvailableYearsProperty(): array
    {
        $requestYears = ProjectRequest::query()
            ->whereNotNull('submitted_at')
            ->selectRaw('YEAR(submitted_at) as year')
            ->distinct()
            ->pluck('year')
            ->filter()
            ->map(fn ($year) => (int) $year);

        $transitionYears = RequestTransition::query()
            ->whereNotNull('acted_at')
            ->selectRaw('YEAR(acted_at) as year')
            ->distinct()
            ->pluck('year')
            ->filter()
            ->map(fn ($year) => (int) $year);

        return $requestYears
            ->merge($transitionYears)
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    protected function resolveWipeCount(): ?int
    {
        $query = ProjectRequest::query();

        if (! $this->applyDateFilter($query, 'submitted_at', $this->wipeMode, $this->wipeFrom, $this->wipeTo, $this->wipeYear)) {
            return null;
        }

        return $query->count();
    }

    protected function resolvePhotoCount(): ?int
    {
        $query = ProjectRequest::query()->whereHas('attachments');

        if ($this->photoMode === 'quarter') {
            if ($this->photoYear === '' || $this->photoQuarter === '') {
                $this->dispatch('notify', type: 'warn', message: 'Select a year and quarter to preview photo purge count.');

                return null;
            }

            [$from, $to] = $this->quarterBounds((int) $this->photoYear, $this->photoQuarter);

            $query->whereBetween('submitted_at', [$from, $to]);

            return $query->count();
        }

        if (! $this->applyCustomRange($query, 'submitted_at', $this->photoFrom, $this->photoTo, 'Select a valid custom date range to preview photo purge count.')) {
            return null;
        }

        return $query->count();
    }

    protected function resolveLogCount(): ?int
    {
        $query = RequestTransition::query();

        if (! $this->applyDateFilter($query, 'acted_at', $this->logMode, $this->logFrom, $this->logTo, $this->logYear)) {
            return null;
        }

        return $query->count();
    }

    protected function applyDateFilter($query, string $column, string $mode, string $from, string $to, string $year): bool
    {
        if ($mode === 'all') {
            return true;
        }

        if ($mode === 'year') {
            if ($year === '') {
                $this->dispatch('notify', type: 'warn', message: 'Select a year before previewing this action.');

                return false;
            }

            $query->whereYear($column, (int) $year);

            return true;
        }

        return $this->applyCustomRange($query, $column, $from, $to, 'Select a valid date range before previewing this action.');
    }

    protected function applyCustomRange($query, string $column, string $from, string $to, string $message): bool
    {
        if ($from === '' || $to === '') {
            $this->dispatch('notify', type: 'warn', message: $message);

            return false;
        }

        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        if ($start->gt($end)) {
            $this->dispatch('notify', type: 'warn', message: $message);

            return false;
        }

        $query->whereBetween($column, [$start, $end]);

        return true;
    }

    protected function quarterBounds(int $year, string $quarter): array
    {
        $startMonth = match ($quarter) {
            'Q1' => 1,
            'Q2' => 4,
            'Q3' => 7,
            default => 10,
        };

        $from = Carbon::create($year, $startMonth, 1)->startOfDay();
        $to = (clone $from)->addMonths(3)->subDay()->endOfDay();

        return [$from, $to];
    }

    protected function confirmationMatchesCount(int $count): bool
    {
        $normalizedInput = str_replace(',', '', trim($this->confirmInput));

        return $normalizedInput !== '' && $normalizedInput === (string) $count;
    }

    public function render()
    {
        return view('livewire.it-admin.danger-zone-page')
            ->layout('layouts.app', [
                'title' => 'Danger Zone | EngiStart',
                'header' => 'Danger Zone',
                'subheader' => 'Preview high-risk maintenance operations and require deliberate confirmation before execution.',
            ]);
    }
}
