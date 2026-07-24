<?php

namespace App\Support;

use App\Models\ProjectRequest;

final class ApprovalChainBuilder
{
    public static function steps(ProjectRequest $request): array
    {
        $transitions = $request->transitions->keyBy('acted_by_role');

        return [
            self::step('Farm Manager', 'done'),
            self::step(
                'Division Head',
                $request->current_owner_role === 'division_head'
                    ? 'pending'
                    : ($transitions->has('division_head') || in_array($request->current_owner_role, ['vp_gen_services', 'dh_gen_services', 'ed_manager', 'engineer'], true)
                        ? 'done'
                        : (in_array($request->current_status, ['returned_to_requestor'], true) ? 'rejected' : 'waiting'))
            ),
            self::step(
                'VP Gen Services',
                $request->current_owner_role === 'vp_gen_services'
                    ? 'pending'
                    : ($transitions->has('vp_gen_services') || in_array($request->current_owner_role, ['ed_manager', 'dh_gen_services', 'engineer'], true)
                        ? 'done'
                        : (in_array($request->current_status, ['returned_to_requestor'], true) && $transitions->has('division_head') ? 'rejected' : 'waiting'))
            ),
            self::step(
                'ED Manager',
                $request->current_owner_role === 'ed_manager'
                    ? 'pending'
                    : ($transitions->has('ed_manager') || in_array($request->current_owner_role, ['dh_gen_services', 'engineer'], true)
                        ? 'done'
                        : 'waiting')
            ),
            self::step(
                'DH Gen Services',
                $request->current_owner_role === 'dh_gen_services'
                    ? 'pending'
                    : ($transitions->has('dh_gen_services') || $request->current_owner_role === 'engineer'
                        ? 'done'
                        : 'waiting')
            ),
            self::step(
                'Engineer',
                $request->current_owner_role === 'engineer' ? 'pending' : ($transitions->has('engineer') ? 'done' : 'waiting')
            ),
        ];
    }

    private static function step(string $role, string $state): array
    {
        return [
            'kind' => 'step',
            'role' => $role,
            'state' => $state,
        ];
    }
}
