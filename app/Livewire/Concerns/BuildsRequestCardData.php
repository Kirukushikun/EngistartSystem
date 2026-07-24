<?php

namespace App\Livewire\Concerns;

use App\Models\ProjectRequest;
use Illuminate\Support\Facades\Storage;

trait BuildsRequestCardData
{
    protected function buildAttachments(ProjectRequest $request): array
    {
        return $request->attachments
            ->where('is_active', true)
            ->filter(fn ($attachment) => in_array($attachment->attachment_type, ['justification_letter', 'supporting_document'], true))
            ->map(function ($attachment): array {
                return [
                    'label' => $attachment->attachment_type === 'justification_letter' ? 'JL File' : 'Attached File',
                    'name' => $attachment->original_name,
                    'url' => Storage::disk($attachment->disk)->url($attachment->path),
                ];
            })
            ->values()
            ->all();
    }

    protected function buildRemarkHistory(ProjectRequest $request): array
    {
        $entries = [];

        foreach ($request->transitions->sortBy('acted_at') as $transition) {
            if ($transition->acted_by_role === 'farm_manager' || blank($transition->remarks)) {
                continue;
            }

            $entries[] = [
                'role' => $this->roleLabel($transition->acted_by_role),
                'actor' => $transition->actedBy?->name ?? 'Unknown approver',
                'label' => $this->remarkLabel($transition->action),
                'remarks' => $transition->remarks,
                'date' => optional($transition->acted_at)->format('Y-m-d h:i A'),
                'tone' => $this->remarkTone($transition->action),
            ];
        }

        return $entries;
    }

    protected function roleLabel(string $role): string
    {
        return match ($role) {
            'farm_manager' => 'Farm Manager',
            'division_head' => 'Division Head',
            'vp_gen_services' => 'VP Gen Services',
            'dh_gen_services' => 'DH Gen Services',
            'ed_manager' => 'ED Manager',
            'engineer' => 'Engineer',
            'it_admin' => 'IT Admin',
            default => str_replace('_', ' ', str($role)->title()),
        };
    }

    protected function remarkLabel(string $action): string
    {
        return match ($action) {
            'recommend', 'recommended' => 'Recommended',
            'approve', 'approved' => 'Approved',
            'noted' => 'Noted',
            'accepted' => 'Accepted',
            'initialized' => 'Initialized',
            'reject', 'rejected' => 'Rejected',
            'return', 'returned' => 'Returned',
            'withdrawn' => 'Withdrawn',
            default => str_replace('_', ' ', str($action)->title()),
        };
    }

    protected function remarkTone(string $action): string
    {
        return match ($action) {
            'recommend', 'recommended', 'approve', 'approved', 'accepted', 'noted', 'initialized' => 'success',
            'reject', 'rejected', 'return', 'returned' => 'danger',
            default => 'info',
        };
    }

    protected function budgetCategoryLabel(?string $category): ?string
    {
        return $category ? config('project_timelines.' . $category . '.label') : null;
    }
}
