<?php

namespace App\Livewire\Guest;

use App\Models\ProjectRequest;
use Illuminate\Support\Collection;
use Livewire\Component;

class FinishedRequestsPage extends Component
{
    public string $search = '';

    public function getFinishedRequestsProperty(): Collection
    {
        $items = ProjectRequest::query()
            ->with('requestor')
            ->orderByDesc('completed_at')
            ->orderByDesc('last_transitioned_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (ProjectRequest $request): array {
                return [
                    'id' => $request->request_number,
                    'title' => $request->title,
                    'farm' => $request->farm_name,
                    'by' => $request->requestor?->name ?? 'Unknown requester',
                    'needed' => optional($request->date_needed)->format('Y-m-d') ?? '—',
                    'completedAt' => optional($request->completed_at ?? $request->last_transitioned_at)->format('Y-m-d h:i A') ?? '—',
                    'status' => $request->current_status,
                    'statusLabel' => $this->statusLabel($request->current_status),
                    'type' => $request->request_type,
                    'purpose' => $request->purpose ?: '—',
                    'desc' => $request->description ?: 'No description provided.',
                    'cap' => $request->capacity,
                    'chickin' => optional($request->chick_in_date)->format('Y-m-d'),
                    'mtgDate' => optional($request->preferred_meeting_date)->format('Y-m-d'),
                    'mtgTime' => $request->preferred_meeting_time,
                ];
            })
            ->values();

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);

            $items = $items->filter(function (array $request) use ($needle): bool {
                return str_contains(mb_strtolower($request['id']), $needle)
                    || str_contains(mb_strtolower($request['title']), $needle)
                    || str_contains(mb_strtolower($request['farm']), $needle)
                    || str_contains(mb_strtolower($request['by']), $needle);
            })->values();
        }

        return $items->sortByDesc('completedAt')->values();
    }

    protected function statusLabel(?string $status): string
    {
        return match ($status) {
            'submitted' => 'Submitted',
            'recommended' => 'DH Recommended',
            'vp_approved' => 'VP Approved',
            'accepted' => 'Accepted',
            'noted' => 'Noted',
            'initialized' => 'Initialized',
            'returned_to_requestor' => 'Returned to Requestor',
            'rejected' => 'Rejected',
            'withdrawn' => 'Withdrawn',
            'jl_pending' => 'JL Under Review',
            'jl_approved' => 'JL Approved',
            null => 'Unknown',
            default => str_replace('_', ' ', str($status)->title()),
        };
    }

    public function render()
    {
        return view('livewire.guest.finished-requests-page')
            ->layout('layouts.app', [
                'title' => 'All Requests | EngiStart',
                'header' => 'All Requests',
                'subheader' => 'View all project requests and their current status.',
            ]);
    }
}
