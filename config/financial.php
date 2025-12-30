<?php

return [
    'breakdown' => [
        'platform_commission_percent' => env('FIN_PLATFORM_COMMISSION_PERCENT', '15'),
        'post_cost_percent' => env('FIN_POST_COST_PERCENT', '30'),
        'temporary_wallet_percent' => env('FIN_TEMPORARY_WALLET_PERCENT', '5'),
        'insurance_percent' => env('FIN_INSURANCE_PERCENT', '5'),
    ],
];

