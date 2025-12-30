<?php

return [
    'breakdown' => [
        // Platform commission: 15%
        'platform_commission_percent' => env('FIN_PLATFORM_COMMISSION_PERCENT', '15'),

        // Post cost: 30%
        'post_cost_percent' => env('FIN_POST_COST_PERCENT', '30'),

        // Temporary wallet: 5%
        'temporary_wallet_percent' => env('FIN_TEMPORARY_WALLET_PERCENT', '5'),

        // Insurance: 5%
        'insurance_percent' => env('FIN_INSURANCE_PERCENT', '5'),
    ],
];
