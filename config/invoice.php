<?php

return [
    'default' => 'classic',

    'designs' => [
        'classic' => [
            'view' => 'pdf.invoices.classic',
            'label' => 'Classic',
            'cover' => '/images/invoices/classic-cover.svg',
        ],
        'minimal' => [
            'view' => 'pdf.invoices.minimal',
            'label' => 'Minimal',
            'cover' => '/images/invoices/minimal-cover.svg',
        ],
        'compact' => [
            'view' => 'pdf.invoices.compact',
            'label' => 'Compact',
            'cover' => '/images/invoices/compact-cover.svg',
        ],
    ],
];
