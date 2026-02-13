<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module Allowlist
    |--------------------------------------------------------------------------
    |
    | Only modules listed here are eligible for registration. A module can
    | still be toggled via `enabled` in the `modules` section below.
    |
    */
    'allowlist' => [
        'MLStandard',
        'MLRetreinamentos',
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Toggles
    |--------------------------------------------------------------------------
    |
    | Use these flags to enable or disable each module independently.
    |
    */
    'modules' => [
        'MLStandard' => [
            'enabled' => env('MODULE_ML_STANDARD_ENABLED', true),
        ],
        'MLRetreinamentos' => [
            'enabled' => env('MODULE_ML_RETREINAMENTOS_ENABLED', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Paths
    |--------------------------------------------------------------------------
    |
    | Paths used by module features that integrate with external services/files.
    |
    */
    'paths' => [
        'ml_retreinamentos' => [
            'dados_retreino_dir' => env(
                'ML_RETREINO_DADOS_DIR',
                storage_path('app/ml_retreinamentos/dados_retreino')
            ),
            'dict_ele_rev_prefix' => env(
                'ML_RETREINO_DICT_ELE_REV_PREFIX',
                storage_path('app/ml_retreinamentos/ele/dicionarios/Rev.')
            ),
            'dict_tub_rev_prefix' => env(
                'ML_RETREINO_DICT_TUB_REV_PREFIX',
                storage_path('app/ml_retreinamentos/tub/dicionarios/Rev.')
            ),
        ],
    ],
];
