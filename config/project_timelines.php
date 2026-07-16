<?php

return [
    'small' => [
        'label' => 'Small (up to ₱200,000)',
        'max_budget' => 200000,
        'start_offset_days' => 30,
        'completion_offset_days' => 45,
    ],
    'medium' => [
        'label' => 'Medium (₱200,001 – ₱600,000)',
        'max_budget' => 600000,
        'start_offset_days' => 45,
        'completion_offset_days' => 75,
    ],
    'large' => [
        'label' => 'Large (above ₱600,000)',
        'max_budget' => null,
        'start_offset_days' => 45,
        'completion_offset_days' => 105,
    ],
];
