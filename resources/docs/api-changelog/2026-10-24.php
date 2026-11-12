<?php

declare(strict_types=1);

return [
    'date' => '2026-10-24',
    'entries' => [
        [
            'id' => 'possession-transactions',
            'kind' => 'Added',
            'group' => 'Possession transactions',
            'title' => 'Adds transactions of a possession',
            'summary' => 'List, get, record, change and delete the money and ownership changing hands for a possession, and attach or remove documents.',
            'why' => 'The acquisition date, the acquisition price and what a possession cost since are read from its transactions.',
            'changes' => [
                ['field' => 'transactions', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/possessions/{possession}/transactions', 'GET /vaults/{vault}/possessions/{possession}/transactions/{transaction}', 'POST /vaults/{vault}/possessions/{possession}/transactions', 'PUT /vaults/{vault}/possessions/{possession}/transactions/{transaction}', 'DELETE /vaults/{vault}/possessions/{possession}/transactions/{transaction}']],
                ['field' => 'documents', 'change' => 'Added', 'endpoints' => ['POST /vaults/{vault}/possessions/{possession}/transactions/{transaction}/documents', 'DELETE /vaults/{vault}/possessions/{possession}/transactions/{transaction}/documents/{file}']],
            ],
        ],
        [
            'id' => 'possession-insurance-records',
            'kind' => 'Added',
            'group' => 'Possession insurance records',
            'title' => 'Adds insurance records of a possession',
            'summary' => 'List, get, add, change and delete how a possession is insured, one record per policy period.',
            'why' => 'A renewal or a change of insurer is a new record, so the earlier ones stay as the history of the coverage.',
            'changes' => [
                ['field' => 'insurance records', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/possessions/{possession}/insurance-records', 'GET /vaults/{vault}/possessions/{possession}/insurance-records/{insuranceRecord}', 'POST /vaults/{vault}/possessions/{possession}/insurance-records', 'PUT /vaults/{vault}/possessions/{possession}/insurance-records/{insuranceRecord}', 'DELETE /vaults/{vault}/possessions/{possession}/insurance-records/{insuranceRecord}']],
            ],
        ],
        [
            'id' => 'possession-maintenance-records',
            'kind' => 'Added',
            'group' => 'Possession maintenance records',
            'title' => 'Adds maintenance records of a possession',
            'summary' => 'List, get, add, change and delete the work done on a possession, one record per job.',
            'why' => 'A record may say what condition the possession was in before and after the job, and when it should be done again.',
            'changes' => [
                ['field' => 'maintenance records', 'change' => 'Added', 'endpoints' => ['GET /vaults/{vault}/possessions/{possession}/maintenance-records', 'GET /vaults/{vault}/possessions/{possession}/maintenance-records/{maintenanceRecord}', 'POST /vaults/{vault}/possessions/{possession}/maintenance-records', 'PUT /vaults/{vault}/possessions/{possession}/maintenance-records/{maintenanceRecord}', 'DELETE /vaults/{vault}/possessions/{possession}/maintenance-records/{maintenanceRecord}']],
            ],
        ],
    ],
];
