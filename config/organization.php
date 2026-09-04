<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Organization Reference Lists
    |--------------------------------------------------------------------------
    |
    | Fixed dropdown options for the IT Admin User Management panel (farm /
    | company and department assignment). Reference data, not user data --
    | see the seeding convention: this is what a form needs to be usable, so
    | it lives in config rather than a seeded table.
    |
    */

    'farms' => [
        'BFC',
        'BFC-IRAQ',
        'BROOKDALE',
        'FEEDMILL',
        'HATCHERY',
        'PFC',
        'RH/BBGC',
    ],

    'departments' => [
        'Accounting',
        'Audit',
        'Feedmill',
        'General Services',
        'Human Resources',
        'IT and Security Services',
        'Poultry',
        'Purchasing',
        'Sales & Marketing',
        'Swine',
        'Treasury',
    ],

];
