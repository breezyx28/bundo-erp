<?php

return [
    // Base accounting currency. All amounts are stored in this currency.
    'base' => 'SDG',

    // Default SDG per 1 USD, used to seed the editable rate captured on each invoice.
    'default_exchange_rate' => (float) env('DEFAULT_EXCHANGE_RATE', 600),

    'currencies' => [
        'SDG' => ['symbol' => 'SDG', 'name' => 'Sudanese Pound', 'decimals' => 2],
        'USD' => ['symbol' => '$', 'name' => 'US Dollar', 'decimals' => 2],
    ],
];
