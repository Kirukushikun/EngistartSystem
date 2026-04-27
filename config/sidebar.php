<?php

return [
    'modules' => [
        'farm-manager' => [
            'role' => 'farm_manager',
            'match' => 'farm-manager.*',
            'items' => [
                [
                    'label' => 'New Request',
                    'route' => 'farm-manager.requests.new',
                    'active' => ['farm-manager.requests.new'],
                ],
                [
                    'label' => 'My Requests',
                    'route' => 'farm-manager.requests.index',
                    'active' => ['farm-manager.requests.index'],
                ],
            ],
        ],
        'division-head' => [
            'role' => 'division_head',
            'match' => 'division-head.*',
            'items' => [
                [
                    'label' => 'For Recommendation',
                    'route' => 'division-head.inbox',
                    'active' => ['division-head.inbox'],
                    'badge' => ['text' => '1', 'tone' => 'blue'],
                ],
                [
                    'label' => 'History',
                    'route' => 'division-head.history',
                    'active' => ['division-head.history'],
                ],
            ],
        ],
        'vp-gen-services' => [
            'role' => 'vp_gen_services',
            'match' => 'vp-gen-services.*',
            'items' => [
                [
                    'label' => 'For Approval',
                    'route' => 'vp-gen-services.inbox',
                    'active' => ['vp-gen-services.inbox'],
                    'badge' => ['text' => '3', 'tone' => 'blue'],
                ],
                [
                    'label' => 'Change Requests',
                    'route' => 'vp-gen-services.change-requests',
                    'active' => ['vp-gen-services.change-requests'],
                    'badge' => ['text' => '3', 'tone' => 'amber'],
                ],
                [
                    'label' => 'History',
                    'route' => 'vp-gen-services.history',
                    'active' => ['vp-gen-services.history'],
                ],
            ],
        ],
        'dh-gen-services' => [
            'role' => 'dh_gen_services',
            'match' => 'dh-gen-services.*',
            'items' => [
                [
                    'label' => 'Late Filing Review',
                    'route' => 'dh-gen-services.late-filings',
                    'active' => ['dh-gen-services.late-filings'],
                    'badge' => ['text' => '3', 'tone' => 'amber'],
                ],
                [
                    'label' => 'For Noting',
                    'route' => 'dh-gen-services.noting',
                    'active' => ['dh-gen-services.noting'],
                    'badge' => ['text' => '3', 'tone' => 'blue'],
                ],
                [
                    'label' => 'History',
                    'route' => 'dh-gen-services.history',
                    'active' => ['dh-gen-services.history'],
                ],
                [
                    'label' => 'Settings Change Request',
                    'route' => 'dh-gen-services.change-request',
                    'active' => ['dh-gen-services.change-request'],
                ],
            ],
        ],
        'ed-manager' => [
            'role' => 'ed_manager',
            'match' => 'ed-manager.*',
            'items' => [
                [
                    'label' => 'For Acceptance',
                    'route' => 'ed-manager.inbox',
                    'active' => ['ed-manager.inbox'],
                    'badge' => ['text' => '3', 'tone' => 'green'],
                ],
                [
                    'label' => 'History',
                    'route' => 'ed-manager.history',
                    'active' => ['ed-manager.history'],
                ],
                [
                    'label' => 'Settings Change Request',
                    'route' => 'ed-manager.change-request',
                    'active' => ['ed-manager.change-request'],
                ],
            ],
        ],
        'it-admin' => [
            'role' => 'it_admin',
            'match' => 'it-admin.*',
            'items' => [
                [
                    'label' => 'All Requests',
                    'route' => 'it-admin.all-requests',
                    'active' => ['it-admin.all-requests'],
                ],
                [
                    'label' => 'User Management',
                    'route' => 'it-admin.users',
                    'active' => ['it-admin.users'],
                ],
                [
                    'label' => 'Audit Trail',
                    'route' => 'it-admin.audit',
                    'active' => ['it-admin.audit'],
                ],
                [
                    'label' => 'Status Override',
                    'route' => 'it-admin.override',
                    'active' => ['it-admin.override'],
                ],
                [
                    'label' => 'Pending Changes',
                    'route' => 'it-admin.pending-changes',
                    'active' => ['it-admin.pending-changes'],
                    'badge' => ['text' => '1', 'tone' => 'blue'],
                ],
                [
                    'label' => 'Settings',
                    'route' => 'it-admin.settings',
                    'active' => ['it-admin.settings'],
                ],
            ],
        ],
        'guest' => [
            'role' => 'guest',
            'match' => 'guest.*',
            'items' => [
                [
                    'label' => 'Finished Requests',
                    'route' => 'guest.finished-requests',
                    'active' => ['guest.finished-requests'],
                ],
            ],
        ],
    ],
];
